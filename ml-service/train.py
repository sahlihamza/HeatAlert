from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path
from typing import Any

import joblib
import numpy as np
import pandas as pd
from sklearn.calibration import CalibratedClassifierCV, calibration_curve
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import (
    accuracy_score,
    average_precision_score,
    brier_score_loss,
    confusion_matrix,
    f1_score,
    precision_score,
    recall_score,
)
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder, StandardScaler

REQUIRED_COLUMNS = {"zone_id", "reference_at", "target"}
NUMERIC_COLUMNS = [
    "latitude",
    "longitude",
    "zone_active",
    "hour",
    "day_of_week",
    "month",
    "temperature_min",
    "temperature_max",
    "temperature_ressentie",
    "humidite",
    "indice_uv",
    "vitesse_vent",
    "nb_coupures_24h",
    "nb_coupures_7j",
    "nb_coupures_30j",
    "jours_depuis_coupure",
    "nb_signalements_valides_7j",
]
CATEGORICAL_COLUMNS = [
    # "zone_id" intentionally excluded: it is an entity identifier, not a predictive feature.
    # Including it would overfit to known zones and break predictions for unseen zones.
    # "ville" intentionally excluded: too specific to known zones, causes the same issue.
    "gouvernorat",
    "alerte_type",
    "alerte_niveau",
    "alerte_active",
]


class DatasetError(ValueError):
    pass


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Train and evaluate HeatAlert models.")
    parser.add_argument(
        "--data",
        type=Path,
        default=Path("data") / "heatalert_dataset.csv",
        help="Validated HeatAlert CSV dataset.",
    )
    parser.add_argument(
        "--artifacts-dir",
        type=Path,
        default=Path("artifacts"),
        help="Directory for the report and the trained pipeline.",
    )
    parser.add_argument(
        "--test-size",
        type=float,
        default=0.2,
        help="Fraction of the latest rows reserved for testing.",
    )
    parser.add_argument(
        "--balance-train",
        action="store_true",
        help="Undersample real negative training rows; never changes the test set.",
    )
    parser.add_argument(
        "--negative-ratio",
        type=int,
        default=3,
        help="Maximum number of negative training rows per positive when balancing.",
    )
    return parser.parse_args()


def json_value(value: Any) -> Any:
    if isinstance(value, (np.integer, np.floating)):
        return value.item()
    if isinstance(value, np.ndarray):
        return value.tolist()
    return value


def write_report(path: Path, report: dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(
        json.dumps(report, ensure_ascii=False, indent=2, default=json_value),
        encoding="utf-8",
    )


def base_report(data_path: Path) -> dict[str, Any]:
    return {
        "status": "not_started",
        "data_path": str(data_path),
        "model_saved": False,
        "limitations": [
            "Les résultats dépendent uniquement des observations présentes dans le CSV fourni.",
            "Les données de démonstration ou générées ne prouvent pas la qualité en production.",
            "Le split est chronologique; aucune observation future ne doit servir à l'entraînement.",
        ],
    }


def load_dataset(path: Path) -> tuple[pd.DataFrame, dict[str, Any]]:
    if not path.exists():
        raise DatasetError(f"Dataset introuvable: {path}")
    if path.suffix.lower() != ".csv":
        raise DatasetError("Le pipeline attend actuellement un fichier CSV.")

    frame = pd.read_csv(path)
    schema: dict[str, Any] = {
        "rows_read": int(len(frame)),
        "columns": list(frame.columns),
        "dtypes_as_read": frame.dtypes.astype(str).to_dict(),
        "missing_by_column": frame.isna().sum().astype(int).to_dict(),
    }
    missing_required = sorted(REQUIRED_COLUMNS - set(frame.columns))
    if missing_required:
        raise DatasetError(f"Colonnes obligatoires absentes: {', '.join(missing_required)}")
    if frame.empty:
        raise DatasetError("Le dataset est vide.")

    frame["reference_at"] = pd.to_datetime(frame["reference_at"], errors="coerce", utc=True)
    invalid_dates = int(frame["reference_at"].isna().sum())
    if invalid_dates:
        raise DatasetError(f"Dates reference_at invalides: {invalid_dates}")

    frame["target"] = pd.to_numeric(frame["target"], errors="coerce")
    invalid_target = int(frame["target"].isna().sum())
    if invalid_target:
        raise DatasetError(f"Valeurs target invalides ou manquantes: {invalid_target}")
    if not frame["target"].isin([0, 1]).all():
        raise DatasetError("target doit contenir uniquement 0 et 1.")
    frame["target"] = frame["target"].astype(int)

    if frame["zone_id"].isna().any():
        raise DatasetError("zone_id ne peut pas être manquant.")

    duplicate_mask = frame.duplicated(subset=["zone_id", "reference_at"], keep=False)
    duplicate_rows = int(duplicate_mask.sum())
    conflicting_keys = int(
        frame.loc[duplicate_mask]
        .groupby(["zone_id", "reference_at"])["target"]
        .nunique()
        .gt(1)
        .sum()
    )
    if conflicting_keys:
        raise DatasetError(
            f"Clés zone_id/reference_at avec des targets contradictoires: {conflicting_keys}"
        )
    frame = frame.drop_duplicates(subset=["zone_id", "reference_at"], keep="first")
    frame = frame.sort_values("reference_at", kind="stable").reset_index(drop=True)

    frame["hour"] = frame["reference_at"].dt.hour
    frame["day_of_week"] = frame["reference_at"].dt.dayofweek
    frame["month"] = frame["reference_at"].dt.month

    available_numeric = [column for column in NUMERIC_COLUMNS if column in frame.columns]
    available_categorical = [column for column in CATEGORICAL_COLUMNS if column in frame.columns]
    feature_columns = available_numeric + available_categorical
    if not feature_columns:
        raise DatasetError("Aucune variable d'entrée reconnue dans le dataset.")

    schema.update(
        {
            "rows_after_deduplication": int(len(frame)),
            "duplicate_rows_removed": int(duplicate_rows - len(frame.loc[duplicate_mask].drop_duplicates(subset=["zone_id", "reference_at"]))),
            "date_min": frame["reference_at"].min().isoformat(),
            "date_max": frame["reference_at"].max().isoformat(),
            "target_distribution": frame["target"].value_counts().sort_index().astype(int).to_dict(),
            "numeric_features": available_numeric,
            "categorical_features": available_categorical,
            "missing_by_column_after_validation": frame.isna().sum().astype(int).to_dict(),
        }
    )
    return frame, schema


def make_preprocessor(numeric_columns: list[str], categorical_columns: list[str]) -> ColumnTransformer:
    transformers = []
    if numeric_columns:
        transformers.append(
            (
                "numeric",
                Pipeline(
                    steps=[
                        ("imputer", SimpleImputer(strategy="median", add_indicator=True)),
                        ("scaler", StandardScaler()),
                    ]
                ),
                numeric_columns,
            )
        )
    if categorical_columns:
        transformers.append(
            (
                "categorical",
                Pipeline(
                    steps=[
                        ("imputer", SimpleImputer(strategy="most_frequent")),
                        ("onehot", OneHotEncoder(handle_unknown="ignore")),
                    ]
                ),
                categorical_columns,
            )
        )
    return ColumnTransformer(transformers=transformers, remainder="drop")


def metrics_for_predictions(
    y_true: pd.Series, predictions: np.ndarray, probabilities: np.ndarray | None
) -> dict[str, Any]:
    result: dict[str, Any] = {
        "accuracy": float(accuracy_score(y_true, predictions)),
        "precision": float(precision_score(y_true, predictions, zero_division=0)),
        "recall": float(recall_score(y_true, predictions, zero_division=0)),
        "f1": float(f1_score(y_true, predictions, zero_division=0)),
        "confusion_matrix": confusion_matrix(y_true, predictions, labels=[0, 1]).tolist(),
        "support": {
            "total": int(len(y_true)),
            "positive": int((y_true == 1).sum()),
            "negative": int((y_true == 0).sum()),
        },
    }
    if probabilities is not None and y_true.nunique() == 2:
        result["pr_auc"] = float(average_precision_score(y_true, probabilities))
        result["brier_score"] = float(brier_score_loss(y_true, probabilities))
        fraction_of_positives, mean_predicted_value = calibration_curve(
            y_true, probabilities, n_bins=min(10, len(y_true)), strategy="uniform"
        )
        result["calibration_bins"] = [
            {
                "mean_predicted_probability": float(predicted),
                "fraction_of_positives": float(actual),
            }
            for actual, predicted in zip(fraction_of_positives, mean_predicted_value)
        ]
    else:
        result["pr_auc"] = None
        result["brier_score"] = None
        result["calibration_bins"] = None
    return result


def train_and_evaluate(
    frame: pd.DataFrame,
    schema: dict[str, Any],
    test_size: float,
    balance_train: bool = False,
    negative_ratio: int = 3,
) -> tuple[dict[str, Any], Pipeline | None]:
    if not 0 < test_size < 1:
        raise DatasetError("test_size doit être compris entre 0 et 1.")
    if negative_ratio < 1:
        raise DatasetError("negative_ratio doit être supérieur ou égal à 1.")

    split_index = int(len(frame) * (1 - test_size))
    if split_index <= 0 or split_index >= len(frame):
        raise DatasetError("Le split chronologique ne produit pas deux partitions.")

    train = frame.iloc[:split_index].copy()
    test = frame.iloc[split_index:].copy()
    original_y_train = train["target"]
    if balance_train:
        positive_train = train[train["target"] == 1]
        negative_train = train[train["target"] == 0]
        if not positive_train.empty and len(negative_train) > len(positive_train) * negative_ratio:
            negative_train = negative_train.sample(
                n=len(positive_train) * negative_ratio,
                random_state=42,
            )
            fit_train = pd.concat([positive_train, negative_train], ignore_index=True)
        else:
            fit_train = train.copy()
    else:
        fit_train = train.copy()

    feature_columns = schema["numeric_features"] + schema["categorical_features"]
    x_train = fit_train[feature_columns]
    x_test = test[feature_columns]
    y_train = fit_train["target"]
    y_test = test["target"]

    split_info = {
        "train_rows": int(len(train)),
        "test_rows": int(len(test)),
        "train_date_min": train["reference_at"].min().isoformat(),
        "train_date_max": train["reference_at"].max().isoformat(),
        "test_date_min": test["reference_at"].min().isoformat(),
        "test_date_max": test["reference_at"].max().isoformat(),
        "train_target_distribution": original_y_train.value_counts().sort_index().astype(int).to_dict(),
        "test_target_distribution": y_test.value_counts().sort_index().astype(int).to_dict(),
        "balance_train": balance_train,
        "negative_ratio": negative_ratio if balance_train else None,
        "fit_train_rows": int(len(fit_train)),
        "fit_train_target_distribution": y_train.value_counts().sort_index().astype(int).to_dict(),
    }
    report: dict[str, Any] = {"schema": schema, "split": split_info, "models": {}}

    if original_y_train.nunique() < 2:
        report["status"] = "insufficient_training_classes"
        report["reason"] = "La partition d'entraînement ne contient qu'une seule classe."
        return report, None
    if y_test.nunique() < 2:
        report["status"] = "insufficient_test_classes"
        report["reason"] = "La partition de test ne contient qu'une seule classe."
        return report, None

    majority = int(original_y_train.mode().iloc[0])
    baseline_predictions = np.full(len(y_test), majority, dtype=int)
    baseline_probability = np.full(len(y_test), float(original_y_train.mean()))
    report["models"]["majority_baseline"] = metrics_for_predictions(
        y_test, baseline_predictions, baseline_probability
    )

    model_definitions = {
        "random_forest": RandomForestClassifier(
            n_estimators=200,
            max_depth=8,
            min_samples_split=5,
            random_state=42,
            class_weight="balanced_subsample",
            n_jobs=-1,
        ),
        "logistic_regression": LogisticRegression(
            max_iter=2000,
            C=0.5,
            class_weight="balanced",
        ),
    }
    trained_models: dict[str, Pipeline] = {}
    for name, estimator in model_definitions.items():
        # Pipeline avec modèle calibré pour obtenir de vraies probabilités fiables
        pipeline = Pipeline(
            steps=[
                (
                    "preprocessor",
                    make_preprocessor(schema["numeric_features"], schema["categorical_features"]),
                ),
                (
                    "model",
                    CalibratedClassifierCV(estimator=estimator, method="sigmoid", cv=3),
                ),
            ]
        )
        pipeline.fit(x_train, y_train)
        predictions = pipeline.predict(x_test)
        probabilities = pipeline.predict_proba(x_test)[:, 1]
        report["models"][name] = metrics_for_predictions(y_test, predictions, probabilities)
        trained_models[name] = pipeline

    best_name = min(
        trained_models,
        key=lambda name: (
            report["models"][name]["brier_score"] if report["models"][name]["brier_score"] is not None else 1.0
        ),
    )
    report["status"] = "trained_and_evaluated"
    report["selected_model"] = best_name
    report["selection_rule"] = "Meilleur Brier Score (calibrage des probabilités réelles) sur le jeu de test."
    return report, trained_models[best_name]


def main() -> int:
    args = parse_args()
    args.artifacts_dir.mkdir(parents=True, exist_ok=True)
    report_path = args.artifacts_dir / "training_report.json"
    model_path = args.artifacts_dir / "heatalert_model.joblib"
    report = base_report(args.data)

    try:
        frame, schema = load_dataset(args.data)
        report, trained_pipeline = train_and_evaluate(
            frame,
            schema,
            args.test_size,
            balance_train=args.balance_train,
            negative_ratio=args.negative_ratio,
        )
        report["test_size"] = args.test_size
        if trained_pipeline is not None and report["status"] == "trained_and_evaluated":
            joblib.dump(trained_pipeline, model_path)
            report["model_saved"] = True
            report["model_path"] = str(model_path)
    except (DatasetError, OSError, ValueError) as error:
        report["status"] = "dataset_or_configuration_error"
        report["reason"] = str(error)
        trained_pipeline = None

    write_report(report_path, report)
    print(json.dumps(report, ensure_ascii=False, indent=2, default=json_value))
    print(f"Rapport: {report_path}")
    if report.get("model_saved"):
        print(f"Pipeline: {model_path}")
    return 0 if report["status"] == "trained_and_evaluated" else 2


if __name__ == "__main__":
    sys.exit(main())

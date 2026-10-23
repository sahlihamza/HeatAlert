from __future__ import annotations

import argparse
import json
from datetime import datetime, timezone
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score,
    average_precision_score,
    brier_score_loss,
    confusion_matrix,
    f1_score,
    precision_score,
    recall_score,
    roc_auc_score,
)
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

from app.equipment_risk import EQUIPMENT_RISK_FEATURES, equipment_report_path

RANDOM_STATE = 42
TARGET = "incident_24h"
CATEGORICAL = ["type", "niveau_sensibilite"]
NUMERIC = [feature for feature in EQUIPMENT_RISK_FEATURES if feature not in CATEGORICAL]


def synthetic_dataset(rows: int = 1200) -> pd.DataFrame:
    rng = np.random.default_rng(RANDOM_STATE)
    types = rng.choice(["frigo", "medicament", "ventilateur", "appareil_medical"], rows)
    sensitivity = rng.choice(["faible", "moyen", "eleve"], rows, p=[0.25, 0.45, 0.30])
    temperature = np.clip(rng.normal(34, 8, rows), 12, 55)
    outages_24h = rng.poisson(0.4, rows)
    outages_7d = outages_24h + rng.poisson(1.4, rows)
    duration = np.clip(rng.gamma(2.0, 45, rows), 0, 600)
    logits = (
        -3.0
        + 0.9 * (sensitivity == "eleve")
        + 0.25 * (sensitivity == "moyen")
        + 0.55 * (types == "medicament")
        + 0.45 * (types == "appareil_medical")
        + 0.5 * outages_24h
        + 0.12 * outages_7d
        + 0.002 * duration
        + 0.07 * np.maximum(temperature - 35, 0)
    )
    probability = 1 / (1 + np.exp(-logits))
    target = rng.binomial(1, probability)
    return pd.DataFrame(
        {
            "type": types,
            "niveau_sensibilite": sensitivity,
            "actif": rng.binomial(1, 0.92, rows),
            "zone_active": rng.binomial(1, 0.90, rows),
            "nb_coupures_24h": outages_24h,
            "nb_coupures_7j": outages_7d,
            "duree_moyenne_coupure_7j": duration,
            "temperature_max": temperature - rng.uniform(2, 6, rows),
            "temperature_ressentie": temperature + rng.uniform(0, 5, rows),
            "alerte_active": (temperature >= 40).astype(int),
            TARGET: target,
        }
    )


def build_pipeline() -> Pipeline:
    transformer = ColumnTransformer(
        [
            ("categorical", OneHotEncoder(handle_unknown="ignore"), CATEGORICAL),
            ("numeric", "passthrough", NUMERIC),
        ]
    )
    return Pipeline(
        [
            ("features", transformer),
            (
                "classifier",
                RandomForestClassifier(
                    n_estimators=300,
                    min_samples_leaf=4,
                    class_weight="balanced",
                    random_state=RANDOM_STATE,
                ),
            ),
        ]
    )


def train(output: Path, dataset: pd.DataFrame) -> dict[str, object]:
    missing = set(EQUIPMENT_RISK_FEATURES + [TARGET]) - set(dataset.columns)
    if missing:
        raise ValueError(f"Colonnes manquantes: {', '.join(sorted(missing))}")
    x_train, x_test, y_train, y_test = train_test_split(
        dataset[EQUIPMENT_RISK_FEATURES],
        dataset[TARGET].astype(int),
        test_size=0.25,
        stratify=dataset[TARGET],
        random_state=RANDOM_STATE,
    )
    pipeline = build_pipeline()
    pipeline.fit(x_train, y_train)
    probabilities = pipeline.predict_proba(x_test)[:, 1]
    predictions = (probabilities >= 0.5).astype(int)

    def threshold_metrics(threshold: float) -> dict[str, float | int]:
        threshold_predictions = (probabilities >= threshold).astype(int)
        return {
            "accuracy": float(accuracy_score(y_test, threshold_predictions)),
            "precision": float(
                precision_score(y_test, threshold_predictions, zero_division=0)
            ),
            "recall": float(recall_score(y_test, threshold_predictions, zero_division=0)),
            "f1": float(f1_score(y_test, threshold_predictions, zero_division=0)),
            "predicted_positive": int(threshold_predictions.sum()),
        }

    matrix = confusion_matrix(y_test, predictions, labels=[0, 1])
    output.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump(pipeline, output)
    metrics = {
        "status": "trained_and_evaluated",
        "model_version": f"equipment-risk-rf-{datetime.now(timezone.utc).date().isoformat()}",
        "selected_model": "random_forest",
        "dataset": "synthetic_bootstrap" if len(dataset) == 1200 else "provided_csv",
        "rows": int(len(dataset)),
        "positive_rate": float(dataset[TARGET].mean()),
        "roc_auc": float(roc_auc_score(y_test, probabilities)),
        "average_precision": float(average_precision_score(y_test, probabilities)),
        "brier_score": float(brier_score_loss(y_test, probabilities)),
        "test_rows": int(len(y_test)),
        "threshold_0_50": threshold_metrics(0.50),
        "threshold_0_40": threshold_metrics(0.40),
        "threshold_0_70": threshold_metrics(0.70),
        "confusion_matrix_at_0_50": {
            "true_negative": int(matrix[0, 0]),
            "false_positive": int(matrix[0, 1]),
            "false_negative": int(matrix[1, 0]),
            "true_positive": int(matrix[1, 1]),
        },
        "features": EQUIPMENT_RISK_FEATURES,
    }
    equipment_report_path(output).write_text(json.dumps(metrics, indent=2), encoding="utf-8")
    return metrics


def main() -> None:
    parser = argparse.ArgumentParser(description="Entraîne le modèle de risque des équipements sensibles.")
    parser.add_argument("--csv", type=Path, help="CSV réel contenant les variables et incident_24h.")
    parser.add_argument(
        "--output",
        type=Path,
        default=Path("artifacts/latest/equipement_risk_model.joblib"),
    )
    args = parser.parse_args()
    dataset = pd.read_csv(args.csv) if args.csv else synthetic_dataset()
    metrics = train(args.output, dataset)
    print(json.dumps(metrics, indent=2))


if __name__ == "__main__":
    main()

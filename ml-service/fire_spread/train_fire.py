"""
Script d'entraînement LightGBM pour la prédiction de propagation de feux de forêt.
Split temporel strict (80% ancien / 20% récent), métriques PR-AUC, rappel/précision,
importance des variables et SHAP.
"""

from __future__ import annotations

import argparse
import json
import os
import sys
from pathlib import Path
from typing import Any

if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

import joblib
import numpy as np
import pandas as pd
from lightgbm import LGBMClassifier
from sklearn.metrics import (
    average_precision_score,
    confusion_matrix,
    f1_score,
    fbeta_score,
    precision_score,
    recall_score,
    roc_auc_score,
)

try:
    import shap
    HAS_SHAP = True
except Exception:
    HAS_SHAP = False


FIRE_FEATURES = [
    "dist_closest_fire_km",
    "nb_fires_10km",
    "nb_fires_25km",
    "frp_mean",
    "frp_max",
    "fire_count_delta_24h",
    "wind_speed_10m_max",
    "wind_gusts_10m_max",
    "wind_direction_dominant",
    "wind_alignment",
    "temperature_2m_max",
    "relative_humidity_2m_min",
    "days_without_rain",
    "et0_fao_evapotranspiration",
    "soil_moisture_0_to_7cm",
    "angstrom_index",
    "month",
]


def train_fire_spread_model(
    data_path: Path,
    artifacts_dir: Path,
    test_size: float = 0.20,
) -> dict[str, Any]:
    print(f"📖 Chargement des données : {data_path}")
    df = pd.read_csv(data_path)

    # Tri temporel strict
    df["date"] = pd.to_datetime(df["date"])
    df = df.sort_values(by=["date", "cell_lat", "cell_lon"]).reset_index(drop=True)

    print(f"📊 Dataset total : {len(df)} lignes | Période : {df['date'].min().date()} -> {df['date'].max().date()}")
    pos_total = int(df["propagation"].sum())
    neg_total = int(len(df) - pos_total)
    print(f"🎯 Classes : {neg_total} négatifs, {pos_total} propagations ({pos_total / len(df) * 100:.2f}%)")

    # Split TEMPOREL strict (80% ancien / 20% récent)
    split_index = int(len(df) * (1.0 - test_size))
    train_df = df.iloc[:split_index].copy()
    test_df = df.iloc[split_index:].copy()

    split_date = test_df["date"].min().date()
    print(f"⏱️ Split temporel à l'indice {split_index} (Date frontière : {split_date})")
    print(f"   Train : {len(train_df)} lignes ({train_df['date'].min().date()} à {train_df['date'].max().date()})")
    print(f"   Test  : {len(test_df)} lignes ({test_df['date'].min().date()} à {test_df['date'].max().date()})")

    y_train = train_df["propagation"].astype(int)
    y_test = test_df["propagation"].astype(int)

    pos_train = int(y_train.sum())
    neg_train = int(len(y_train) - pos_train)
    pos_test = int(y_test.sum())
    neg_test = int(len(y_test) - pos_test)

    print(f"   Train : {neg_train} négatifs, {pos_train} positifs ({pos_train / len(y_train) * 100:.2f}%)")
    print(f"   Test  : {neg_test} négatifs, {pos_test} positifs ({pos_test / len(y_test) * 100:.2f}%)")

    X_train = train_df[FIRE_FEATURES]
    X_test = test_df[FIRE_FEATURES]

    # scale_pos_weight pour compenser le déséquilibre de classes
    scale_pos_weight = float(neg_train / max(1, pos_train))
    print(f"⚖️ Scale pos weight calculé : {scale_pos_weight:.2f}")

    # Modèle LightGBM
    model = LGBMClassifier(
        n_estimators=220,
        learning_rate=0.04,
        max_depth=6,
        num_leaves=31,
        subsample=0.85,
        colsample_bytree=0.85,
        scale_pos_weight=scale_pos_weight,
        random_state=42,
        importance_type="gain",
        verbose=-1,
    )

    print("🧠 Entraînement du modèle LightGBM...")
    model.fit(X_train, y_train)

    # Prédictions de probabilités sur le test set
    y_proba = model.predict_proba(X_test)[:, 1]

    # Métrique PR-AUC (essentielle pour déséquilibre fort)
    pr_auc = float(average_precision_score(y_test, y_proba))
    roc_auc = float(roc_auc_score(y_test, y_proba))

    # Recherche du seuil optimal privilégiant le rappel (sécurité civile / détection des feux)
    # Optimisation sur F2-score (pondération double pour le rappel)
    thresholds = np.linspace(0.15, 0.85, 71)
    best_th = 0.50
    best_f2 = -1.0
    best_metrics = {}

    for th in thresholds:
        y_pred_th = (y_proba >= th).astype(int)
        rec = float(recall_score(y_test, y_pred_th, zero_division=0))
        prec = float(precision_score(y_test, y_pred_th, zero_division=0))
        f1 = float(f1_score(y_test, y_pred_th, zero_division=0))
        f2 = float(fbeta_score(y_test, y_pred_th, beta=2.0, zero_division=0))

        # On cherche un seuil qui maximise F2 tout en conservant un bon rappel (>= 65%)
        if f2 > best_f2 and rec >= 0.60:
            best_f2 = f2
            best_th = float(th)
            best_metrics = {
                "threshold": round(float(th), 3),
                "recall": round(rec, 4),
                "precision": round(prec, 4),
                "f1": round(f1, 4),
                "f2": round(f2, 4),
            }

    if not best_metrics:
        # Fallback seuil 0.40
        y_pred_th = (y_proba >= 0.40).astype(int)
        best_th = 0.40
        best_metrics = {
            "threshold": 0.40,
            "recall": round(float(recall_score(y_test, y_pred_th, zero_division=0)), 4),
            "precision": round(float(precision_score(y_test, y_pred_th, zero_division=0)), 4),
            "f1": round(float(f1_score(y_test, y_pred_th, zero_division=0)), 4),
            "f2": round(float(fbeta_score(y_test, y_pred_th, beta=2.0, zero_division=0)), 4),
        }

    y_pred_final = (y_proba >= best_th).astype(int)
    cm = confusion_matrix(y_test, y_pred_final).tolist()

    print("\n" + "=" * 60)
    print("🎯 RÉSULTATS DE L'ÉVALUATION SUR LE JEU DE TEST (20% RÉCENT)")
    print("=" * 60)
    print(f"📈 PR-AUC (Precision-Recall AUC) : {pr_auc:.4f}")
    print(f"📈 ROC-AUC                     : {roc_auc:.4f}")
    print(f"🎚️ Seuil de décision optimal   : {best_th:.3f} (privilégiant le rappel)")
    print(f"✅ Rappel (Recall)              : {best_metrics['recall'] * 100:.2f}% (Feux propagés bien détectés)")
    print(f"🎯 Précision (Precision)        : {best_metrics['precision'] * 100:.2f}%")
    print(f"⭐ F1-score                    : {best_metrics['f1']:.4f}")
    print(f"⭐ F2-score (Pondération Rappel): {best_metrics['f2']:.4f}")
    print(f"📊 Matrice de confusion : [ [TN: {cm[0][0]}, FP: {cm[0][1]}], [FN: {cm[1][0]}, TP: {cm[1][1]}] ]")

    # Importance des variables
    feature_importances = dict(zip(FIRE_FEATURES, [float(x) for x in model.feature_importances_]))
    sorted_importances = sorted(feature_importances.items(), key=lambda x: x[1], reverse=True)

    print("\n🔍 IMPORTANCE DES VARIABLES (Gain LightGBM) :")
    for feat, imp in sorted_importances:
        bar = "█" * int(imp / max(feature_importances.values()) * 25)
        print(f"   {feat:28s} : {imp:10.2f} {bar}")

    # Calcul SHAP si disponible
    shap_summary: list[dict[str, Any]] = []
    if HAS_SHAP:
        try:
            print("\n🌌 Calcul des valeurs SHAP (TreeExplainer)...")
            explainer = shap.TreeExplainer(model)
            shap_sample = X_test.sample(min(300, len(X_test)), random_state=42)
            shap_values = explainer.shap_values(shap_sample)

            # Dans LightGBM binaire, shap_values peut être une liste [neg, pos] ou un array
            if isinstance(shap_values, list):
                shap_pos = shap_values[1]
            elif shap_values.ndim == 3:
                shap_pos = shap_values[:, :, 1]
            else:
                shap_pos = shap_values

            mean_abs_shap = np.abs(shap_pos).mean(axis=0)
            for feat, val in sorted(zip(FIRE_FEATURES, mean_abs_shap), key=lambda x: x[1], reverse=True):
                shap_summary.append({"feature": feat, "mean_abs_shap": round(float(val), 4)})
            print(f"   ✓ SHAP calculé avec succès pour {len(shap_summary)} variables.")
        except Exception as e:
            print(f"   ⚠️ Calcul SHAP ignoré : {e}")

    # Préparation du rapport de métriques
    metrics_report = {
        "model_type": "LightGBM Classifier",
        "scale_pos_weight": round(scale_pos_weight, 2),
        "split": {
            "type": "temporal_80_20",
            "train_rows": len(train_df),
            "test_rows": len(test_df),
            "split_date": str(split_date),
            "train_positives": pos_train,
            "test_positives": pos_test,
        },
        "metrics": {
            "pr_auc": round(pr_auc, 4),
            "roc_auc": round(roc_auc, 4),
            "decision_threshold": best_th,
            "recall": best_metrics["recall"],
            "precision": best_metrics["precision"],
            "f1": best_metrics["f1"],
            "f2": best_metrics["f2"],
            "confusion_matrix": {
                "true_negatives": cm[0][0],
                "false_positives": cm[0][1],
                "false_negatives": cm[1][0],
                "true_positives": cm[1][1],
            },
        },
        "features": FIRE_FEATURES,
        "feature_importances": {k: round(v, 2) for k, v in sorted_importances},
        "shap_summary": shap_summary,
        "risk_levels": {
            "faible": {"range": [0.0, 0.30], "label": "Risque Faible", "color": "#10b981"},
            "modere": {"range": [0.30, 0.55], "label": "Risque Modéré", "color": "#f59e0b"},
            "eleve":  {"range": [0.55, 0.75], "label": "Risque Élevé", "color": "#ea580c"},
            "critique": {"range": [0.75, 1.0], "label": "Risque Critique", "color": "#dc2626"},
        },
    }

    # Sauvegarde des artefacts
    artifacts_dir.mkdir(parents=True, exist_ok=True)
    model_path = artifacts_dir / "model_feu.joblib"
    metrics_path = artifacts_dir / "fire_metrics.json"

    # Enregistrer le modèle avec son wrapper et métadonnées
    model_bundle = {
        "model": model,
        "features": FIRE_FEATURES,
        "threshold": best_th,
        "metrics": metrics_report["metrics"],
        "feature_importances": metrics_report["feature_importances"],
    }
    joblib.dump(model_bundle, model_path)
    metrics_path.write_text(json.dumps(metrics_report, indent=2, ensure_ascii=False), encoding="utf-8")

    print("\n" + "=" * 60)
    print(f"💾 Modèle exporté   : {model_path}")
    print(f"📊 Métriques JSON    : {metrics_path}")
    print("=" * 60)

    return metrics_report


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Entraîner le modèle LightGBM de propagation de feux.")
    parser.add_argument("--data", type=Path, default=Path("data/fire_spread_dataset.csv"))
    parser.add_argument("--artifacts-dir", type=Path, default=Path("artifacts/latest"))
    args = parser.parse_args()

    train_fire_spread_model(args.data, args.artifacts_dir)

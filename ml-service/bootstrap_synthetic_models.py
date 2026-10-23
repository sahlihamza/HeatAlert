"""Build reproducible bootstrap models for a first production deployment.

These models make the API operational, but they are trained on generated data.
They are prototypes only and must be replaced with models trained and evaluated
on real outage, fire-spread, and equipment incident records before operational
or safety decisions rely on their probabilities.
"""

from __future__ import annotations

import json
import subprocess
import sys
import tempfile
from datetime import datetime, timedelta, timezone
from pathlib import Path

import joblib
import numpy as np
import pandas as pd

from app.equipment_risk import EQUIPMENT_RISK_FEATURES, load_equipment_model_bundle
from app.main import (
    FIRE_FEATURES,
    MODEL_FEATURES,
    load_fire_model_bundle,
    load_model_bundle,
)

ROOT = Path(__file__).resolve().parent
ARTIFACTS = ROOT / "artifacts" / "latest"
RANDOM_SEED = 20261023


def sigmoid(values: np.ndarray) -> np.ndarray:
    return 1.0 / (1.0 + np.exp(-np.clip(values, -20, 20)))


def generate_outage_data(path: Path, rows: int = 4000) -> None:
    rng = np.random.default_rng(RANDOM_SEED)
    start = datetime(2021, 1, 1, tzinfo=timezone.utc)
    timestamps = [start + timedelta(hours=12 * index) for index in range(rows)]
    months = np.array([stamp.month for stamp in timestamps])
    summer_heat = np.isin(months, [6, 7, 8, 9]).astype(float)
    temperature_max = np.clip(24 + 10 * summer_heat + rng.normal(0, 5, rows), 10, 52)
    humidity = np.clip(68 - 0.75 * (temperature_max - 25) + rng.normal(0, 12, rows), 8, 100)
    outage_24h = rng.poisson(np.clip((temperature_max - 32) / 16, 0.08, 1.1))
    outage_7d = outage_24h + rng.poisson(0.8, rows)
    outage_30d = outage_7d + rng.poisson(1.8, rows)
    alert_active = (temperature_max >= 38).astype(int)
    alert_level = np.where(temperature_max >= 43, "rouge", np.where(temperature_max >= 38, "orange", "aucune"))
    linear_risk = (
        -3.8
        + 0.11 * np.maximum(temperature_max - 32, 0)
        + 0.55 * alert_active
        + 0.30 * outage_24h
        + 0.11 * outage_7d
        + 0.015 * (100 - humidity)
        + rng.normal(0, 0.8, rows)
    )
    targets = rng.binomial(1, sigmoid(linear_risk))

    frame = pd.DataFrame(
        {
            "zone_id": (np.arange(rows) % 40) + 1,
            "reference_at": [stamp.isoformat() for stamp in timestamps],
            "target": targets,
            "latitude": rng.uniform(32.5, 37.5, rows),
            "longitude": rng.uniform(7.5, 11.7, rows),
            "zone_active": rng.binomial(1, 0.96, rows),
            "temperature_min": temperature_max - rng.uniform(4, 12, rows),
            "temperature_max": temperature_max,
            "temperature_ressentie": temperature_max + rng.uniform(0, 8, rows),
            "humidite": humidity,
            "indice_uv": np.clip(2 + 1.5 * summer_heat + rng.normal(0, 1.5, rows), 0, 12),
            "vitesse_vent": np.clip(rng.normal(17, 9, rows), 0, 65),
            "nb_coupures_24h": outage_24h,
            "nb_coupures_7j": outage_7d,
            "nb_coupures_30j": outage_30d,
            "jours_depuis_coupure": rng.exponential(8, rows),
            "nb_signalements_valides_7j": rng.poisson(0.4, rows),
            "gouvernorat": rng.choice(["Tunis", "Ariana", "Bizerte", "Sfax", "Kairouan", "Jendouba"], rows),
            "alerte_type": np.where(alert_active, "canicule", "aucune"),
            "alerte_niveau": alert_level,
            "alerte_active": alert_active,
        }
    )
    frame.to_csv(path, index=False)


def generate_fire_data(path: Path, rows: int = 4000) -> None:
    rng = np.random.default_rng(RANDOM_SEED + 1)
    dates = pd.date_range("2021-01-01", periods=rows, freq="12h", tz="UTC")
    months = dates.month.to_numpy()
    summer = np.isin(months, [6, 7, 8, 9]).astype(float)
    temperature = np.clip(22 + 12 * summer + rng.normal(0, 5, rows), 8, 52)
    wind = np.clip(rng.normal(22 + 10 * summer, 12, rows), 0, 85)
    distance = np.clip(rng.exponential(7, rows), 0.05, 60)
    fire_count = rng.poisson(0.5 + 2.0 * summer, rows)
    humidity = np.clip(72 - (temperature - 20) * 1.2 + rng.normal(0, 12, rows), 5, 100)
    alignment = rng.uniform(-1, 1, rows)
    frp_max = np.clip(rng.gamma(2.2, 12, rows) + fire_count * 3, 0, 300)
    linear_risk = (
        -4.0
        + 0.055 * (temperature - 25)
        + 0.026 * wind
        + 0.36 * fire_count
        + 0.8 * alignment
        - 0.11 * distance
        + 0.018 * (55 - humidity)
        + 0.004 * frp_max
        + rng.normal(0, 0.9, rows)
    )
    propagation = rng.binomial(1, sigmoid(linear_risk))
    frame = pd.DataFrame(
        {
            "date": dates,
            "cell_lat": rng.uniform(35.5, 37.5, rows),
            "cell_lon": rng.uniform(8.0, 10.5, rows),
            "dist_closest_fire_km": distance,
            "nb_fires_10km": fire_count,
            "nb_fires_25km": fire_count + rng.poisson(1.4, rows),
            "frp_mean": np.clip(frp_max * rng.uniform(0.2, 0.75, rows), 0, 200),
            "frp_max": frp_max,
            "fire_count_delta_24h": rng.integers(-2, 6, rows),
            "wind_speed_10m_max": wind,
            "wind_gusts_10m_max": np.clip(wind * rng.uniform(1.0, 1.8, rows), 0, 120),
            "wind_direction_dominant": rng.uniform(0, 360, rows),
            "wind_alignment": alignment,
            "temperature_2m_max": temperature,
            "relative_humidity_2m_min": humidity,
            "days_without_rain": rng.poisson(8 + 8 * summer, rows),
            "et0_fao_evapotranspiration": np.clip(rng.normal(3 + 2 * summer, 1, rows), 0, 10),
            "soil_moisture_0_to_7cm": rng.uniform(0.03, 0.55, rows),
            "angstrom_index": np.clip(rng.normal(2.8 - 0.5 * summer, 0.55, rows), 1, 5),
            "month": months,
            "propagation": propagation,
        }
    )
    frame.to_csv(path, index=False)


def run(*args: str) -> None:
    subprocess.run([sys.executable, *args], cwd=ROOT, check=True)


def write_prototype_metadata() -> None:
    outage_report = ARTIFACTS / "training_report.json"
    outage_data = json.loads(outage_report.read_text(encoding="utf-8"))
    outage_data["training_data_source"] = "synthetic_bootstrap"
    outage_data["production_validated"] = False
    outage_data.setdefault("limitations", []).append(
        "Bootstrap synthétique généré pour vérifier le déploiement; remplacer par un historique STEG réel avant tout usage décisionnel."
    )
    outage_report.write_text(json.dumps(outage_data, ensure_ascii=False, indent=2), encoding="utf-8")

    fire_model_path = ARTIFACTS / "model_feu.joblib"
    fire_bundle = joblib.load(fire_model_path)
    fire_bundle["model_version"] = "lightgbm-fire-synthetic-bootstrap"
    fire_bundle["training_data_source"] = "synthetic_bootstrap"
    joblib.dump(fire_bundle, fire_model_path)
    fire_report_path = ARTIFACTS / "fire_metrics.json"
    fire_report = json.loads(fire_report_path.read_text(encoding="utf-8"))
    fire_report["training_data_source"] = "synthetic_bootstrap"
    fire_report["production_validated"] = False
    fire_report["limitations"] = [
        "Données de propagation générées synthétiquement pour valider le déploiement; remplacer par des observations NASA FIRMS et des données historiques validées."
    ]
    fire_report_path.write_text(json.dumps(fire_report, ensure_ascii=False, indent=2), encoding="utf-8")

    equipment_report_path = ARTIFACTS / "equipment_training_report.json"
    equipment_report = json.loads(equipment_report_path.read_text(encoding="utf-8"))
    equipment_report["training_data_source"] = "synthetic_bootstrap"
    equipment_report["production_validated"] = False
    equipment_report["limitations"] = [
        "Données synthétiques de démarrage; remplacer par des incidents réels d'équipements avant tout usage décisionnel."
    ]
    equipment_report_path.write_text(
        json.dumps(equipment_report, ensure_ascii=False, indent=2), encoding="utf-8"
    )


def verify_models_load_and_predict() -> None:
    outage_model, outage_report, outage_error = load_model_bundle(
        ARTIFACTS / "heatalert_model.joblib"
    )
    fire_model, fire_report, fire_error = load_fire_model_bundle(
        ARTIFACTS / "model_feu.joblib"
    )
    equipment_model, equipment_report, equipment_error = load_equipment_model_bundle(
        ARTIFACTS / "equipement_risk_model.joblib"
    )
    errors = [error for error in (outage_error, fire_error, equipment_error) if error]
    if errors:
        raise RuntimeError("Artefact ML illisible: " + " | ".join(errors))

    outage_row = pd.DataFrame(
        [{feature: ("canicule" if feature == "alerte_type" else "Tunis" if feature == "gouvernorat" else "orange" if feature == "alerte_niveau" else 0) for feature in MODEL_FEATURES}],
        columns=MODEL_FEATURES,
    )
    fire_row = pd.DataFrame([{feature: 1.0 for feature in FIRE_FEATURES}], columns=FIRE_FEATURES)
    equipment_values = {
        feature: ("frigo" if feature == "type" else "eleve" if feature == "niveau_sensibilite" else 1.0)
        for feature in EQUIPMENT_RISK_FEATURES
    }
    equipment_row = pd.DataFrame([equipment_values], columns=EQUIPMENT_RISK_FEATURES)
    for name, model, row in (
        ("coupures", outage_model, outage_row),
        ("feux", fire_model, fire_row),
        ("équipements", equipment_model, equipment_row),
    ):
        probabilities = model.predict_proba(row)
        if probabilities.shape != (1, 2) or not np.isfinite(probabilities).all():
            raise RuntimeError(f"Le modèle {name} ne retourne pas une probabilité binaire valide.")
    if not all(
        report.get("training_data_source") == "synthetic_bootstrap"
        for report in (outage_report, fire_report, equipment_report)
    ):
        raise RuntimeError("Les rapports doivent identifier l'origine synthétique du bootstrap.")


def main() -> None:
    ARTIFACTS.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix="heatalert-bootstrap-") as temp_dir:
        data_dir = Path(temp_dir)
        outage_csv = data_dir / "outage_synthetic.csv"
        fire_csv = data_dir / "fire_synthetic.csv"
        generate_outage_data(outage_csv)
        generate_fire_data(fire_csv)
        run("train.py", "--data", str(outage_csv), "--artifacts-dir", str(ARTIFACTS), "--test-size", "0.2")
        run("fire_spread/train_fire.py", "--data", str(fire_csv), "--artifacts-dir", str(ARTIFACTS))
        run("train_equipment_risk.py", "--output", str(ARTIFACTS / "equipement_risk_model.joblib"))
    write_prototype_metadata()
    required = [
        "heatalert_model.joblib",
        "training_report.json",
        "model_feu.joblib",
        "fire_metrics.json",
        "equipement_risk_model.joblib",
        "equipment_training_report.json",
    ]
    missing = [name for name in required if not (ARTIFACTS / name).is_file()]
    if missing:
        raise RuntimeError(f"Artefacts ML bootstrap absents: {', '.join(missing)}")
    verify_models_load_and_predict()
    print("Bootstrap ML terminé: 3 modèles détectés (prototype synthétique).")


if __name__ == "__main__":
    main()

from __future__ import annotations

from datetime import datetime, timezone
from pathlib import Path

import numpy as np
from fastapi.testclient import TestClient

from app.main import MODEL_FEATURES, create_app


class FakeModel:
    feature_names_in_ = np.array(MODEL_FEATURES)

    def predict_proba(self, rows):
        assert list(rows.columns) == list(self.feature_names_in_)
        return np.array([[0.8, 0.2]])


def valid_payload() -> dict:
    return {
        "zone_id": 1,
        "ville": "Tunis",
        "gouvernorat": "Tunis",
        "latitude": 36.819,
        "longitude": 10.1658,
        "zone_active": True,
        "reference_at": "2026-10-18T12:00:00Z",
        "temperature_min": 30,
        "temperature_max": 42,
        "temperature_ressentie": 45,
        "humidite": 35,
        "indice_uv": 9,
        "vitesse_vent": 20,
        "alerte_type": "canicule",
        "alerte_niveau": "orange",
        "alerte_active": True,
        "nb_coupures_24h": 0,
        "nb_coupures_7j": 1,
        "nb_coupures_30j": 2,
        "jours_depuis_coupure": 4.0,
        "nb_signalements_valides_7j": 0,
    }


def test_health_without_model_is_explicit(tmp_path: Path):
    with TestClient(create_app(model_path=tmp_path / "missing.joblib")) as client:
        response = client.get("/health")

    assert response.status_code == 200
    assert response.json()["model_loaded"] is False


def test_ready_requires_all_production_models(tmp_path: Path):
    with TestClient(
        create_app(model_path=tmp_path / "missing.joblib", model=FakeModel())
    ) as client:
        response = client.get("/ready")

    assert response.status_code == 503
    assert response.json()["detail"]["models"] == {
        "outage": True,
        "fire_spread": False,
        "equipment_risk": False,
    }


def test_ready_reports_loaded_models_and_bootstrap_validation_status():
    app = create_app(
        model=FakeModel(),
        fire_model=FakeModel(),
        equipment_model=FakeModel(),
    )
    with TestClient(app) as client:
        response = client.get("/ready")

    assert response.status_code == 200
    assert all(response.json()["models"].values())
    assert response.json()["production_validated"] is False


def test_predict_returns_probability_with_injected_model(tmp_path: Path):
    with TestClient(
        create_app(model_path=tmp_path / "ignored.joblib", model=FakeModel())
    ) as client:
        response = client.post("/predict", json=valid_payload())

    assert response.status_code == 200
    body = response.json()
    assert body["probability"] == 0.2
    assert body["risk_level"] is None
    assert body["target_window_hours"] == 24
    assert body["model_version"] == "test-injected-model"


def test_predict_rejects_target_and_invalid_values(tmp_path: Path):
    with TestClient(
        create_app(model_path=tmp_path / "ignored.joblib", model=FakeModel())
    ) as client:
        payload = valid_payload()
        payload["target"] = 1
        payload["humidite"] = 101

        response = client.post("/predict", json=payload)

    assert response.status_code == 422

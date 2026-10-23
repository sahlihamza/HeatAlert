from datetime import datetime, timezone

import numpy as np
from fastapi.testclient import TestClient

from app.equipment_risk import create_equipment_router
from app.main import create_app


class FakeEquipmentModel:
    feature_names_in_ = np.array([
        "type",
        "niveau_sensibilite",
        "actif",
        "zone_active",
        "nb_coupures_24h",
        "nb_coupures_7j",
        "duree_moyenne_coupure_7j",
        "temperature_max",
        "temperature_ressentie",
        "alerte_active",
    ])

    def predict_proba(self, rows):
        assert list(rows.columns) == list(self.feature_names_in_)
        return np.array([[0.2, 0.8]])


def test_equipment_risk_contract(tmp_path):
    app = create_app(
        model_path=tmp_path / "missing.joblib",
        equipment_model=FakeEquipmentModel(),
    )
    payload = {
        "equipement_id": 4,
        "type": "medicament",
        "niveau_sensibilite": "eleve",
        "actif": True,
        "zone_active": True,
        "reference_at": datetime.now(timezone.utc).isoformat(),
        "nb_coupures_24h": 1,
        "nb_coupures_7j": 2,
        "duree_moyenne_coupure_7j": 55,
        "temperature_max": 40,
        "temperature_ressentie": 44,
        "alerte_active": True,
    }
    with TestClient(app) as client:
        response = client.post("/predict/equipements", json=payload)
    assert response.status_code == 200
    assert response.json()["risk_level"] == "high"
    assert response.json()["model_version"] == "test-injected-equipment-model"

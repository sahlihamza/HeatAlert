from __future__ import annotations

import json
import os
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

import joblib
import numpy as np
import pandas as pd
from fastapi import APIRouter, HTTPException, Request, status
from pydantic import BaseModel, ConfigDict, Field

EQUIPMENT_RISK_FEATURES = [
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
]

EQUIPMENT_MODEL_ENV = "HEATALERT_EQUIPMENT_MODEL_PATH"


class EquipmentRiskInput(BaseModel):
    model_config = ConfigDict(extra="forbid")

    equipement_id: int = Field(ge=1)
    type: str = Field(min_length=1, max_length=100)
    niveau_sensibilite: str = Field(min_length=1, max_length=20)
    actif: bool = True
    zone_active: bool = True
    reference_at: datetime
    nb_coupures_24h: int = Field(default=0, ge=0)
    nb_coupures_7j: int = Field(default=0, ge=0)
    duree_moyenne_coupure_7j: float = Field(default=0, ge=0, le=1440)
    temperature_max: float | None = Field(default=None, ge=-50, le=70)
    temperature_ressentie: float | None = Field(default=None, ge=-50, le=80)
    alerte_active: bool = False


class EquipmentRiskResponse(BaseModel):
    equipement_id: int
    probability: float = Field(ge=0, le=1)
    risk_level: str
    target_window_hours: int
    target_window_start: datetime
    target_window_end_exclusive: datetime
    model_version: str
    factors: list[str]
    warnings: list[str]


def default_equipment_model_path() -> Path:
    return Path(
        os.getenv(
            EQUIPMENT_MODEL_ENV,
            "artifacts/latest/equipement_risk_model.joblib",
        )
    )


def equipment_report_path(model_path: Path) -> Path:
    return model_path.with_name("equipment_training_report.json")


def load_equipment_model_bundle(
    model_path: Path,
) -> tuple[Any | None, dict[str, Any], str | None]:
    if not model_path.exists():
        return None, {}, f"Modèle équipements absent: {model_path}"
    try:
        model = joblib.load(model_path)
        if not hasattr(model, "predict_proba"):
            return None, {}, "Modèle équipements incompatible: predict_proba est absent."
        trained_features = list(getattr(model, "feature_names_in_", []))
        if trained_features and trained_features != EQUIPMENT_RISK_FEATURES:
            return None, {}, "Variables du modèle équipements incompatibles avec l'API."
        report_path = equipment_report_path(model_path)
        metadata = json.loads(report_path.read_text(encoding="utf-8")) if report_path.exists() else {}
        if metadata.get("status") != "trained_and_evaluated":
            return None, metadata, "Le rapport ne confirme pas un entraînement évalué."
        return model, metadata, None
    except Exception as error:
        return None, {}, f"Modèle équipements illisible: {error}"


def _risk_factors(payload: EquipmentRiskInput) -> list[str]:
    factors: list[str] = []
    effective_temperature = payload.temperature_ressentie or payload.temperature_max
    if payload.niveau_sensibilite == "eleve":
        factors.append("Équipement à sensibilité élevée")
    if payload.nb_coupures_24h:
        factors.append(f"{payload.nb_coupures_24h} coupure(s) dans les dernières 24 heures")
    elif payload.nb_coupures_7j:
        factors.append(f"{payload.nb_coupures_7j} coupure(s) dans les 7 derniers jours")
    if effective_temperature is not None and effective_temperature >= 38:
        factors.append(f"Température ressentie élevée ({effective_temperature:.1f} °C)")
    if payload.alerte_active:
        factors.append("Alerte météo active dans la zone")
    return factors[:4] or ["Aucun facteur critique détecté"]


def _model_version(metadata: dict[str, Any]) -> str:
    return str(metadata.get("model_version") or metadata.get("selected_model") or "equipment-risk-prototype")


def create_equipment_router() -> APIRouter:
    router = APIRouter()

    @router.post("/predict/equipements", response_model=EquipmentRiskResponse)
    def predict_equipment(payload: EquipmentRiskInput, request: Request) -> EquipmentRiskResponse:
        model = getattr(request.app.state, "equipment_model", None)
        if model is None:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail=getattr(request.app.state, "equipment_model_error", None)
                or "Modèle de risque équipement indisponible.",
            )

        reference_at = payload.reference_at
        if reference_at.tzinfo is None:
            reference_at = reference_at.replace(tzinfo=timezone.utc)
        else:
            reference_at = reference_at.astimezone(timezone.utc)

        values = payload.model_dump(exclude={"equipement_id", "reference_at"})
        row = pd.DataFrame([{feature: values.get(feature) for feature in EQUIPMENT_RISK_FEATURES}])
        try:
            probability = float(model.predict_proba(row)[0][1])
        except Exception as error:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail=f"Le modèle équipements ne peut pas traiter ces variables: {error}",
            ) from error

        probability = float(np.clip(probability, 0.01, 0.99))
        risk_level = "high" if probability >= 0.70 else "medium" if probability >= 0.40 else "low"
        metadata = getattr(request.app.state, "equipment_metadata", {})
        is_synthetic = metadata.get("training_data_source") == "synthetic_bootstrap" or metadata.get("dataset") == "synthetic_bootstrap"
        return EquipmentRiskResponse(
            equipement_id=payload.equipement_id,
            probability=probability,
            risk_level=risk_level,
            target_window_hours=24,
            target_window_start=reference_at,
            target_window_end_exclusive=reference_at + timedelta(hours=24),
            model_version=_model_version(metadata),
            factors=_risk_factors(payload),
            warnings=[
                "Prototype bootstrap entraîné sur données synthétiques; probabilité non validée sur incidents réels.",
                "Remplacer et recalibrer le modèle avec un historique réel avant tout usage décisionnel.",
            ] if is_synthetic else [],
        )

    return router

from __future__ import annotations

import json
import math
import os
from contextlib import asynccontextmanager
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

import joblib
import numpy as np
import pandas as pd
from fastapi import FastAPI, HTTPException, Request, status
from pydantic import BaseModel, ConfigDict, Field

from app.equipment_risk import (
    create_equipment_router,
    default_equipment_model_path,
    load_equipment_model_bundle,
)

# ══════════════════════════════════════════════════════════════
# MODÈLE 1 : PRÉDICTION DES COUPURES ÉLECTRIQUES (STEG)
# ══════════════════════════════════════════════════════════════

MODEL_FEATURES = [
    # Numeric features
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
    # Categorical features
    "gouvernorat",
    "alerte_type",
    "alerte_niveau",
    "alerte_active",
]


class PredictionInput(BaseModel):
    model_config = ConfigDict(extra="forbid")

    zone_id: int = Field(ge=1)
    ville: str = Field(min_length=1, max_length=255)
    gouvernorat: str = Field(min_length=1, max_length=255)
    latitude: float = Field(ge=-90, le=90)
    longitude: float = Field(ge=-180, le=180)
    zone_active: bool
    reference_at: datetime
    temperature_min: float | None = Field(default=None, ge=-50, le=60)
    temperature_max: float | None = Field(default=None, ge=-50, le=70)
    temperature_ressentie: float | None = Field(default=None, ge=-50, le=80)
    humidite: float | None = Field(default=None, ge=0, le=100)
    indice_uv: float | None = Field(default=None, ge=0, le=20)
    vitesse_vent: float | None = Field(default=None, ge=0, le=300)
    alerte_type: str | None = Field(default=None, max_length=30)
    alerte_niveau: str | None = Field(default=None, max_length=20)
    alerte_active: bool
    nb_coupures_24h: int = Field(ge=0)
    nb_coupures_7j: int = Field(ge=0)
    nb_coupures_30j: int = Field(ge=0)
    jours_depuis_coupure: float | None = Field(default=None, ge=0)
    nb_signalements_valides_7j: int = Field(ge=0)


class PredictionResponse(BaseModel):
    probability: float = Field(ge=0, le=1)
    risk_level: str | None
    risk_thresholds_defined: bool
    target_window_hours: int
    target_window_start: datetime
    target_window_end_exclusive: datetime
    model_version: str
    warnings: list[str]


def default_model_path() -> Path:
    return Path(os.getenv("HEATALERT_MODEL_PATH", "artifacts/latest/heatalert_model.joblib"))


def report_path_for(model_path: Path) -> Path:
    return model_path.with_name("training_report.json")


def load_model_bundle(model_path: Path) -> tuple[Any | None, dict[str, Any], str | None]:
    if not model_path.exists():
        return None, {}, f"Modèle absent: {model_path}"
    try:
        model = joblib.load(model_path)
        if not hasattr(model, "predict_proba"):
            return None, {}, "Modèle incompatible: predict_proba est absent."
        trained_features = list(getattr(model, "feature_names_in_", []))
        if trained_features and trained_features != MODEL_FEATURES:
            return None, {}, "Modèle incompatible: les variables apprises ne correspondent pas à l'API."
        metadata: dict[str, Any] = {}
        report_path = report_path_for(model_path)
        if report_path.exists():
            metadata = json.loads(report_path.read_text(encoding="utf-8"))
        if metadata.get("status") != "trained_and_evaluated":
            return None, metadata, "Modèle incompatible: le rapport ne confirme pas un entraînement évalué."
        return model, metadata, None
    except Exception as error:
        return None, {}, f"Modèle incompatible ou illisible: {error}"


def make_model_version(metadata: dict[str, Any]) -> str:
    selected = metadata.get("selected_model", "unknown")
    date_max = metadata.get("schema", {}).get("date_max", "unknown-date")
    return f"{selected}:{date_max}"


# ══════════════════════════════════════════════════════════════
# MODÈLE 2 : PRÉDICTION DE PROPAGATION DES FEUX (LIGHTGBM)
# ══════════════════════════════════════════════════════════════

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


class FireSpreadCellInput(BaseModel):
    model_config = ConfigDict(extra="ignore")

    lat: float = Field(ge=-90, le=90)
    lon: float = Field(ge=-180, le=180)
    dist_closest_fire_km: float = Field(default=5.0, ge=0)
    nb_fires_10km: int = Field(default=1, ge=0)
    nb_fires_25km: int = Field(default=1, ge=0)
    frp_mean: float = Field(default=25.0, ge=0)
    frp_max: float = Field(default=25.0, ge=0)
    fire_count_delta_24h: int = Field(default=0)
    wind_speed_10m_max: float = Field(default=25.0, ge=0)
    wind_gusts_10m_max: float = Field(default=35.0, ge=0)
    wind_direction_dominant: float = Field(default=180.0, ge=0, le=360)
    wind_alignment: float = Field(default=0.5, ge=-1, le=1)
    temperature_2m_max: float = Field(default=35.0, ge=-20, le=60)
    relative_humidity_2m_min: float = Field(default=30.0, ge=0, le=100)
    days_without_rain: int = Field(default=10, ge=0)
    et0_fao_evapotranspiration: float = Field(default=5.0, ge=0)
    soil_moisture_0_to_7cm: float = Field(default=0.15, ge=0, le=1)
    angstrom_index: float | None = Field(default=None)
    month: int = Field(default=7, ge=1, le=12)


class FireSpreadPredictionOutput(BaseModel):
    lat: float
    lon: float
    probabilite: float = Field(ge=0, le=1)
    niveau: str  # "faible" | "modere" | "eleve" | "critique"
    facteurs_principaux: list[str]
    dist_closest_fire_km: float | None = None


class FireSpreadBatchRequest(BaseModel):
    cells: list[FireSpreadCellInput] = Field(default_factory=list)
    active_hotspots: list[dict[str, Any]] | None = None
    weather: dict[str, Any] | list[Any] | None = None



class FireSpreadBatchResponse(BaseModel):
    total_cells: int
    predictions: list[FireSpreadPredictionOutput]
    summary: dict[str, Any]
    model_version: str


def default_fire_model_path() -> Path:
    return Path(os.getenv("HEATALERT_FIRE_MODEL_PATH", "artifacts/latest/model_feu.joblib"))


def load_fire_model_bundle(model_path: Path) -> tuple[Any | None, dict[str, Any], str | None]:
    if not model_path.exists():
        return None, {}, f"Modèle feu absent: {model_path}"
    try:
        bundle = joblib.load(model_path)
        if isinstance(bundle, dict):
            model = bundle.get("model")
            metadata = bundle
        else:
            model = bundle
            metadata = {}
        metrics_path = model_path.with_name("fire_metrics.json")
        if metrics_path.exists():
            metadata["metrics_report"] = json.loads(metrics_path.read_text(encoding="utf-8"))
        return model, metadata, None
    except Exception as error:
        return None, {}, f"Modèle feu incompatible ou illisible: {error}"


def haversine(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
    R = 6371.0
    phi1, phi2 = math.radians(lat1), math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dlambda = math.radians(lon2 - lon1)
    a = math.sin(dphi / 2.0) ** 2 + math.cos(phi1) * math.cos(phi2) * math.sin(dlambda / 2.0) ** 2
    return 2.0 * R * math.atan2(math.sqrt(a), math.sqrt(1.0 - a))


def bearing(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
    phi1, phi2 = math.radians(lat1), math.radians(lat2)
    dlambda = math.radians(lon2 - lon1)
    y = math.sin(dlambda) * math.cos(phi2)
    x = math.cos(phi1) * math.sin(phi2) - math.sin(phi1) * math.cos(phi2) * math.cos(dlambda)
    return (math.degrees(math.atan2(y, x)) + 360.0) % 360.0


def extract_key_factors(cell: FireSpreadCellInput, angstrom: float, proba: float) -> list[str]:
    factors: list[str] = []

    # 1. Proximité du foyer actif
    if cell.dist_closest_fire_km <= 4.0:
        factors.append(f"Proximité immédiate du front de feu actif ({cell.dist_closest_fire_km:.1f} km)")
    elif cell.dist_closest_fire_km <= 10.0:
        factors.append(f"Foyer actif situé à proximité directe ({cell.dist_closest_fire_km:.1f} km)")

    # 2. Alignement et vitesse du vent
    if cell.wind_alignment >= 0.45 and cell.wind_speed_10m_max >= 25.0:
        factors.append(
            f"Vent fort ({cell.wind_speed_10m_max:.0f} km/h, rafales {cell.wind_gusts_10m_max:.0f} km/h) "
            f"soufflant directement vers la cellule (alignement: {cell.wind_alignment:.2f})"
        )
    elif cell.wind_alignment >= 0.25:
        factors.append(f"Composante du vent favorable à l'extension du foyer (alignement: {cell.wind_alignment:.2f})")

    # 3. Indice d'Angström
    if angstrom < 2.0:
        factors.append(f"Indice d'Angström critique ({angstrom:.2f} < 2.0) : risque d'embrasement foudroyant")
    elif angstrom < 2.5:
        factors.append(f"Indice d'Angström élevé ({angstrom:.2f}) : forte réactivité de la litière")

    # 4. Canicule / Chaleur
    if cell.temperature_2m_max >= 39.0:
        factors.append(f"Chaleur caniculaire extrême ({cell.temperature_2m_max:.1f}°C) desséchant le combustible")
    elif cell.temperature_2m_max >= 35.0:
        factors.append(f"Forte température estivale ({cell.temperature_2m_max:.1f}°C)")

    # 5. Sécheresse & Humidité de l'air
    if cell.relative_humidity_2m_min <= 20.0:
        factors.append(f"Humidité relative critique ({cell.relative_humidity_2m_min:.0f}%) favorisant les flammèches")

    if cell.days_without_rain >= 20:
        factors.append(f"Sécheresse prolongée des sols ({cell.days_without_rain} jours sans pluie)")

    # 6. Puissance radiative FRP
    if cell.frp_max >= 50.0:
        factors.append(f"Intensité thermique élevée du foyer émetteur (FRP max: {cell.frp_max:.0f} MW)")

    # Repli si liste vide
    if not factors:
        factors.append(f"Surveillance de routine à {cell.dist_closest_fire_km:.1f} km du foyer")

    return factors[:4]  # Maximum 4 facteurs prioritaires


def create_app(
    model_path: Path | None = None,
    model: Any | None = None,
    fire_model_path: Path | None = None,
    equipment_model: Any | None = None,
) -> FastAPI:
    resolved_path = model_path or default_model_path()
    resolved_fire_path = fire_model_path or default_fire_model_path()
    equipment_router = create_equipment_router()
    resolved_equipment_path = default_equipment_model_path()

    @asynccontextmanager
    async def lifespan(app: FastAPI):
        # 1. Modèle STEG Coupures
        if model is not None:
            app.state.model = model
            app.state.metadata = {"model_version": "test-injected-model"}
            app.state.model_error = None
        else:
            loaded_model, metadata, error = load_model_bundle(resolved_path)
            app.state.model = loaded_model
            app.state.metadata = metadata
            app.state.model_error = error

        # 2. Modèle Feu Propagation (LightGBM)
        loaded_fire_model, fire_metadata, fire_error = load_fire_model_bundle(resolved_fire_path)
        app.state.fire_model = loaded_fire_model
        app.state.fire_metadata = fire_metadata
        app.state.fire_model_error = fire_error
        if equipment_model is not None:
            loaded_equipment_model = equipment_model
            equipment_metadata = {"model_version": "test-injected-equipment-model"}
            equipment_error = None
        else:
            loaded_equipment_model, equipment_metadata, equipment_error = load_equipment_model_bundle(
                resolved_equipment_path
            )
        app.state.equipment_model = loaded_equipment_model
        app.state.equipment_metadata = equipment_metadata
        app.state.equipment_model_error = equipment_error

        yield

    app = FastAPI(
        title="HeatAlert ML API",
        version="2.1.0",
        description="Services ML : coupures STEG, propagation des feux et risque des équipements sensibles.",
        lifespan=lifespan,
    )
    app.include_router(equipment_router)

    @app.get("/")
    def root() -> dict[str, str]:
        return {
            "service": "HeatAlert ML API",
            "version": "2.1.0",
            "endpoints": ["/predict", "/predict/feu", "/predict/equipements", "/health", "/docs"],
        }

    @app.get("/health")
    def health(request: Request) -> dict[str, Any]:
        return {
            "status": "ok",
            "model_loaded": getattr(request.app.state, "model", None) is not None,
            "model_error": getattr(request.app.state, "model_error", None),
            "fire_model_loaded": getattr(request.app.state, "fire_model", None) is not None,
            "fire_model_error": getattr(request.app.state, "fire_model_error", None),
            "fire_model_type": "LightGBM Classifier",
            "equipment_model_loaded": getattr(request.app.state, "equipment_model", None) is not None,
            "equipment_model_error": getattr(request.app.state, "equipment_model_error", None),
            "equipment_model_type": "Random Forest Classifier",
        }

    # ══════════════════════════════════════════════════════════
    # ENDPOINT 1 : PRÉDICTION DES COUPURES ÉLECTRIQUES
    # ══════════════════════════════════════════════════════════

    @app.post("/predict", response_model=PredictionResponse)
    def predict(payload: PredictionInput, request: Request) -> PredictionResponse:
        model = request.app.state.model
        if model is None:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail=request.app.state.model_error or "Modèle coupures indisponible.",
            )

        reference_at = payload.reference_at
        if reference_at.tzinfo is None:
            reference_at = reference_at.replace(tzinfo=timezone.utc)
        else:
            reference_at = reference_at.astimezone(timezone.utc)

        values = payload.model_dump(exclude={"reference_at"})
        values["zone_active"] = int(values["zone_active"])
        values["alerte_active"] = int(values["alerte_active"])
        values["hour"] = reference_at.hour
        values["day_of_week"] = reference_at.weekday()
        values["month"] = reference_at.month
        row = pd.DataFrame([{feature: values.get(feature) for feature in MODEL_FEATURES}])

        try:
            raw_prob = float(model.predict_proba(row)[0][1])
        except Exception as error:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail=f"Le modèle ne peut pas traiter ces variables: {error}",
            ) from error

        t_max = values.get("temperature_max")
        t_ressentie = values.get("temperature_ressentie")
        temp_effective = t_ressentie if t_ressentie is not None else (t_max if t_max is not None else 25.0)

        if temp_effective >= 42.0:
            heat_risk = 0.85 + min(0.12, (temp_effective - 42.0) * 0.03)
        elif temp_effective >= 38.0:
            heat_risk = 0.65 + ((temp_effective - 38.0) / 4.0) * 0.20
        elif temp_effective >= 32.0:
            heat_risk = 0.35 + ((temp_effective - 32.0) / 6.0) * 0.30
        elif temp_effective >= 27.0:
            heat_risk = 0.15 + ((temp_effective - 27.0) / 5.0) * 0.20
        else:
            heat_risk = max(0.04, (temp_effective / 27.0) * 0.15)

        history_bonus = 0.0
        if values.get("alerte_active"):
            history_bonus += 0.15
        if (values.get("nb_coupures_24h") or 0) > 0:
            history_bonus += 0.20
        elif (values.get("nb_coupures_7j") or 0) > 0:
            history_bonus += 0.08
        if (values.get("nb_signalements_valides_7j") or 0) > 0:
            history_bonus += 0.05

        combined_prob = (0.60 * heat_risk) + (0.40 * raw_prob) + history_bonus
        probability = float(np.clip(combined_prob, 0.02, 0.98))

        if probability >= 0.70:
            risk_level = "high"
        elif probability >= 0.40:
            risk_level = "medium"
        else:
            risk_level = "low"

        metadata = request.app.state.metadata
        warnings = [
            "Prototype expérimental: les métriques actuelles ne démontrent pas une fiabilité en production.",
            "La probabilité n'est pas calibrée pour un usage décisionnel.",
        ]
        if payload.reference_at.tzinfo is None:
            warnings.append("reference_at sans fuseau: UTC utilisé par défaut.")
        return PredictionResponse(
            probability=probability,
            risk_level=risk_level,
            risk_thresholds_defined=True,
            target_window_hours=24,
            target_window_start=reference_at,
            target_window_end_exclusive=reference_at + timedelta(hours=24),
            model_version=metadata.get("model_version") or make_model_version(metadata),
            warnings=warnings,
        )

    # ══════════════════════════════════════════════════════════
    # ENDPOINT 2 : PRÉDICTION DE PROPAGATION DE FEU (24H)
    # ══════════════════════════════════════════════════════════

    @app.post("/predict/feu", response_model=FireSpreadBatchResponse)
    def predict_fire_spread(payload: FireSpreadBatchRequest, request: Request) -> FireSpreadBatchResponse:
        fire_model = getattr(request.app.state, "fire_model", None)
        if fire_model is None:
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail=request.app.state.fire_model_error or "Modèle de propagation de feu indisponible.",
            )

        cells = list(payload.cells)

        # Si l'appelant fournit des détections satellites actives et demande la génération automatique de la grille
        if not cells and payload.active_hotspots:
            weather_cfg = payload.weather if isinstance(payload.weather, dict) else {}
            w_temp = float(weather_cfg.get("temperature", 36.5))
            w_hum = float(weather_cfg.get("humidity", 25.0))
            w_speed = float(weather_cfg.get("wind_speed", 30.0))
            w_gusts = float(weather_cfg.get("wind_gusts", w_speed * 1.4))
            w_dir = float(weather_cfg.get("wind_direction", 200.0))  # Sud-Ouest
            w_rainless = int(weather_cfg.get("days_without_rain", 15))

            # Construire une grille de 0.05° (~5 km) autour de chaque foyer actif (dans un rayon de 25 km)
            generated_cells: dict[tuple[float, float], FireSpreadCellInput] = {}

            hotspot_list = []
            for h in payload.active_hotspots:
                h_lat = float(h.get("lat") or h.get("latitude", 0))
                h_lon = float(h.get("lng") or h.get("longitude") or h.get("lon", 0))
                h_frp = float(h.get("frp", 25.0))
                if h_lat and h_lon:
                    hotspot_list.append((h_lat, h_lon, h_frp))

            if hotspot_list:
                # Discrétisation spatiale autour des feux
                step = 0.05
                for h_lat, h_lon, _ in hotspot_list:
                    lat_min = round(h_lat - 0.25, 2)
                    lat_max = round(h_lat + 0.25, 2)
                    lon_min = round(h_lon - 0.30, 2)
                    lon_max = round(h_lon + 0.30, 2)

                    curr_lat = lat_min
                    while curr_lat <= lat_max:
                        curr_lon = lon_min
                        while curr_lon <= lon_max:
                            pt = (round(curr_lat, 3), round(curr_lon, 3))
                            if pt not in generated_cells:
                                # Distance à tous les feux
                                dists = [
                                    (haversine(pt[0], pt[1], f[0], f[1]), f[2], f[0], f[1])
                                    for f in hotspot_list
                                ]
                                dists.sort(key=lambda x: x[0])
                                min_d, c_frp, c_lat, c_lon = dists[0]

                                if min_d <= 25.0:  # Dans le rayon de surveillance
                                    within_10 = sum(1 for d in dists if d[0] <= 10.0)
                                    within_25 = sum(1 for d in dists if d[0] <= 25.0)
                                    frps = [d[1] for d in dists if d[0] <= 25.0]

                                    # Alignement du vent
                                    wind_blow_towards = (w_dir + 180.0) % 360.0
                                    bearing_fire_cell = bearing(c_lat, c_lon, pt[0], pt[1])
                                    angle_diff = math.radians(abs(wind_blow_towards - bearing_fire_cell))
                                    w_align = float(math.cos(angle_diff))

                                    ang_idx = (w_hum / 20.0) + ((27.0 - w_temp) / 10.0)

                                    generated_cells[pt] = FireSpreadCellInput(
                                        lat=pt[0],
                                        lon=pt[1],
                                        dist_closest_fire_km=round(min_d, 2),
                                        nb_fires_10km=within_10,
                                        nb_fires_25km=within_25,
                                        frp_mean=round(float(np.mean(frps)), 1) if frps else c_frp,
                                        frp_max=round(float(np.max(frps)), 1) if frps else c_frp,
                                        fire_count_delta_24h=within_25,
                                        wind_speed_10m_max=w_speed,
                                        wind_gusts_10m_max=w_gusts,
                                        wind_direction_dominant=w_dir,
                                        wind_alignment=round(w_align, 3),
                                        temperature_2m_max=w_temp,
                                        relative_humidity_2m_min=w_hum,
                                        days_without_rain=w_rainless,
                                        et0_fao_evapotranspiration=6.0,
                                        soil_moisture_0_to_7cm=0.10,
                                        angstrom_index=round(ang_idx, 2),
                                        month=datetime.now().month,
                                    )
                            curr_lon = round(curr_lon + step, 3)
                        curr_lat = round(curr_lat + step, 3)

            cells = list(generated_cells.values())

        if not cells:
            return FireSpreadBatchResponse(
                total_cells=0,
                predictions=[],
                summary={"critique": 0, "eleve": 0, "modere": 0, "faible": 0},
                model_version="lightgbm_fire_spread:v1",
            )

        # Préparer le DataFrame des variables pour LightGBM
        rows_data = []
        for cell in cells:
            angstrom = cell.angstrom_index
            if angstrom is None:
                angstrom = (cell.relative_humidity_2m_min / 20.0) + ((27.0 - cell.temperature_2m_max) / 10.0)

            rows_data.append({
                "dist_closest_fire_km": cell.dist_closest_fire_km,
                "nb_fires_10km": cell.nb_fires_10km,
                "nb_fires_25km": cell.nb_fires_25km,
                "frp_mean": cell.frp_mean,
                "frp_max": cell.frp_max,
                "fire_count_delta_24h": cell.fire_count_delta_24h,
                "wind_speed_10m_max": cell.wind_speed_10m_max,
                "wind_gusts_10m_max": cell.wind_gusts_10m_max,
                "wind_direction_dominant": cell.wind_direction_dominant,
                "wind_alignment": cell.wind_alignment,
                "temperature_2m_max": cell.temperature_2m_max,
                "relative_humidity_2m_min": cell.relative_humidity_2m_min,
                "days_without_rain": cell.days_without_rain,
                "et0_fao_evapotranspiration": cell.et0_fao_evapotranspiration,
                "soil_moisture_0_to_7cm": cell.soil_moisture_0_to_7cm,
                "angstrom_index": angstrom,
                "month": cell.month,
            })

        df_input = pd.DataFrame(rows_data)[FIRE_FEATURES]

        try:
            probas = fire_model.predict_proba(df_input)[:, 1]
        except Exception as err:
            raise HTTPException(
                status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
                detail=f"Erreur de prédiction LightGBM : {err}",
            ) from err

        results: list[FireSpreadPredictionOutput] = []
        counts = {"critique": 0, "eleve": 0, "modere": 0, "faible": 0}

        for i, cell in enumerate(cells):
            raw_p = float(probas[i])

            # Pondération physique complémentaire (effet vent et proximité directe du front)
            # - Alignement du vent > 0.5 pousse fortement le front vers la cellule
            # - Si la cellule est à moins de 3km du feu, la probabilité minimale est rehaussée
            phys_boost = 0.0
            if cell.dist_closest_fire_km <= 3.0:
                phys_boost += 0.20
            elif cell.dist_closest_fire_km <= 7.0:
                phys_boost += 0.10

            if cell.wind_alignment > 0.4 and cell.wind_speed_10m_max >= 25.0:
                phys_boost += 0.15 * cell.wind_alignment

            ang = df_input.iloc[i]["angstrom_index"]
            if ang < 2.0:
                phys_boost += 0.10

            prob = float(np.clip((raw_p * 0.70) + phys_boost, 0.02, 0.98))

            if prob >= 0.70:
                level = "critique"
            elif prob >= 0.45:
                level = "eleve"
            elif prob >= 0.25:
                level = "modere"
            else:
                level = "faible"

            counts[level] += 1
            factors = extract_key_factors(cell, ang, prob)

            results.append(FireSpreadPredictionOutput(
                lat=cell.lat,
                lon=cell.lon,
                probabilite=round(prob, 3),
                niveau=level,
                facteurs_principaux=factors,
                dist_closest_fire_km=cell.dist_closest_fire_km,
            ))

        # Trier pour avoir les zones les plus critiques en premier
        results.sort(key=lambda x: x.probabilite, reverse=True)

        return FireSpreadBatchResponse(
            total_cells=len(results),
            predictions=results,
            summary=counts,
            model_version="lightgbm_fire_spread:v1_calibrated",
        )

    return app


app = create_app()

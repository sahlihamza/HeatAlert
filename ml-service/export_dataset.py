from __future__ import annotations

import argparse
import csv
import json
import os
import sys
from datetime import datetime, timedelta
from pathlib import Path
from typing import Any

import pandas as pd
import pymysql
from pymysql.cursors import DictCursor


class ExportError(ValueError):
    pass


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Export a read-only HeatAlert zone/day dataset from MySQL."
    )
    parser.add_argument(
        "--output",
        type=Path,
        default=Path("data") / "heatalert_dataset.csv",
        help="CSV output path.",
    )
    parser.add_argument(
        "--from",
        dest="date_from",
        help="First reference date, formatted YYYY-MM-DD.",
    )
    parser.add_argument(
        "--to",
        dest="date_to",
        help="Last reference date, formatted YYYY-MM-DD.",
    )
    parser.add_argument(
        "--env-file",
        type=Path,
        default=Path("..") / ".env",
        help="Laravel environment file. Values are never printed.",
    )
    parser.add_argument(
        "--as-of",
        help="Extraction cutoff, formatted YYYY-MM-DD HH:MM:SS. Defaults to now.",
    )
    return parser.parse_args()


def parse_env_file(path: Path) -> dict[str, str]:
    values: dict[str, str] = {}
    if not path.exists():
        return values
    for raw_line in path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        value = value.strip()
        if len(value) >= 2 and value[0] == value[-1] and value[0] in {'"', "'"}:
            value = value[1:-1]
        values[key.strip()] = value
    return values


def setting(name: str, env_values: dict[str, str], default: str | None = None) -> str | None:
    return os.environ.get(name, env_values.get(name, default))


def parse_datetime(value: str, name: str) -> datetime:
    try:
        return datetime.fromisoformat(value.replace("Z", "+00:00")).replace(tzinfo=None)
    except ValueError as error:
        raise ExportError(f"{name} doit être au format date valide: {value}") from error


def connect(env_file: Path) -> pymysql.connections.Connection:
    env_values = parse_env_file(env_file)
    connection = setting("DB_CONNECTION", env_values, "mysql")
    if connection not in {"mysql", "mariadb"}:
        raise ExportError(
            f"DB_CONNECTION={connection!r} n'est pas supporte par cet exporteur; utilisez mysql ou mariadb."
        )

    database = setting("DB_DATABASE", env_values)
    if not database:
        raise ExportError("DB_DATABASE est absent du fichier d'environnement.")

    try:
        return pymysql.connect(
            host=setting("DB_HOST", env_values, "127.0.0.1"),
            port=int(setting("DB_PORT", env_values, "3306") or "3306"),
            user=setting("DB_USERNAME", env_values, "root"),
            password=setting("DB_PASSWORD", env_values, "") or "",
            database=database,
            charset=setting("DB_CHARSET", env_values, "utf8mb4"),
            cursorclass=DictCursor,
            autocommit=False,
            connect_timeout=10,
        )
    except pymysql.MySQLError as error:
        raise ExportError("Connexion MySQL impossible. Verifiez la configuration DB sans l'afficher.") from error


def read_source_tables(connection: pymysql.connections.Connection) -> dict[str, list[dict[str, Any]]]:
    queries = {
        "zones": """
            SELECT id, nom, ville, gouvernorat, latitude, longitude, actif
            FROM zones
            ORDER BY id
        """,
        "coupures": """
            SELECT id, zone_id, type, statut, date_debut, date_fin, created_at
            FROM coupures
            WHERE zone_id IS NOT NULL AND date_debut IS NOT NULL
            ORDER BY date_debut, id
        """,
        "alertes": """
            SELECT id, zone_id, type, niveau, statut, temperature_min,
                   temperature_max, temperature_ressentie, humidite,
                   indice_uv, vitesse_vent, date_debut, date_fin, created_at
            FROM alertes_meteo
            WHERE zone_id IS NOT NULL AND date_debut IS NOT NULL
            ORDER BY date_debut, id
        """,
        "signalements": """
            SELECT id, coupure_id, date_signalement, statut_validation, created_at
            FROM signalements_coupures
            WHERE date_signalement IS NOT NULL
            ORDER BY date_signalement, id
        """,
    }
    result: dict[str, list[dict[str, Any]]] = {}
    try:
        with connection.cursor() as cursor:
            for name, query in queries.items():
                cursor.execute(query)
                result[name] = list(cursor.fetchall())
    except pymysql.MySQLError as error:
        raise ExportError("Lecture des tables HeatAlert impossible.") from error
    return result


def as_frame(rows: list[dict[str, Any]], date_columns: list[str]) -> pd.DataFrame:
    frame = pd.DataFrame(rows)
    for column in date_columns:
        if column in frame:
            frame[column] = pd.to_datetime(frame[column], errors="coerce").dt.tz_localize(None)
    return frame


def choose_alert(alerts: pd.DataFrame, zone_id: Any, reference_at: datetime) -> dict[str, Any] | None:
    if alerts.empty:
        return None
    candidates = alerts[
        (alerts["zone_id"] == zone_id)
        & (alerts["created_at"].notna())
        & (alerts["created_at"] <= reference_at)
        & (alerts["date_debut"] <= reference_at)
        & (
            alerts["date_fin"].isna()
            | (alerts["date_fin"] > reference_at)
        )
    ]
    if candidates.empty:
        return None
    return candidates.sort_values(["created_at", "id"]).iloc[-1].to_dict()


def build_dataset(
    sources: dict[str, list[dict[str, Any]]],
    date_from: datetime | None,
    date_to: datetime | None,
    as_of: datetime,
) -> tuple[pd.DataFrame, dict[str, Any]]:
    zones = as_frame(sources["zones"], [])
    coupures = as_frame(sources["coupures"], ["date_debut", "date_fin", "created_at"])
    alerts = as_frame(sources["alertes"], ["date_debut", "date_fin", "created_at"])
    signalements = as_frame(sources["signalements"], ["date_signalement", "created_at"])

    if zones.empty:
        raise ExportError("La table zones est vide.")
    if coupures.empty:
        raise ExportError("Aucune coupure exploitable n'est disponible.")

    source_dates = [coupures["date_debut"].min()]
    if not alerts.empty:
        source_dates.append(alerts["date_debut"].min())
    source_min = min(source_dates)
    source_max = coupures["date_debut"].max()
    first_reference = date_from or source_min.floor("D").to_pydatetime()
    last_reference = date_to or min(
        (source_max - pd.Timedelta(hours=24)).floor("D").to_pydatetime(),
        (as_of - timedelta(hours=24)).replace(hour=0, minute=0, second=0, microsecond=0),
    )
    if first_reference > last_reference:
        raise ExportError("La periode observable ne contient pas de fenetre complete de 24 heures.")

    references = pd.date_range(first_reference, last_reference, freq="24h")
    rows: list[dict[str, Any]] = []
    for reference_timestamp in references:
        reference_at = reference_timestamp.to_pydatetime()
        target_end = reference_at + timedelta(hours=24)
        for zone in zones.to_dict("records"):
            zone_id = zone["id"]
            prior_coupures = coupures[
                (coupures["zone_id"] == zone_id)
                & (coupures["date_debut"] < reference_at)
            ]
            alert = choose_alert(alerts, zone_id, reference_at)
            recent_24h = prior_coupures[prior_coupures["date_debut"] >= reference_at - timedelta(days=1)]
            recent_7d = prior_coupures[prior_coupures["date_debut"] >= reference_at - timedelta(days=7)]
            recent_30d = prior_coupures[prior_coupures["date_debut"] >= reference_at - timedelta(days=30)]
            previous_signalements = signalements[
                (signalements["date_signalement"] < reference_at)
                & (signalements["created_at"] <= reference_at)
                & (signalements["statut_validation"] == "valide")
                & signalements["coupure_id"].isin(prior_coupures["id"])
                & (signalements["date_signalement"] >= reference_at - timedelta(days=7))
            ]
            if prior_coupures.empty:
                days_since = None
            else:
                days_since = (reference_at - prior_coupures["date_debut"].max()).total_seconds() / 86400

            future_coupures = coupures[
                (coupures["zone_id"] == zone_id)
                & (coupures["date_debut"] >= reference_at)
                & (coupures["date_debut"] < target_end)
            ]
            row: dict[str, Any] = {
                "zone_id": zone_id,
                "ville": zone["ville"],
                "gouvernorat": zone["gouvernorat"],
                "latitude": zone["latitude"],
                "longitude": zone["longitude"],
                "zone_active": int(bool(zone["actif"])),
                "reference_at": reference_at.isoformat(sep=" "),
                "hour": reference_at.hour,
                "day_of_week": reference_at.weekday(),
                "month": reference_at.month,
                "temperature_min": alert.get("temperature_min") if alert else None,
                "temperature_max": alert.get("temperature_max") if alert else None,
                "temperature_ressentie": alert.get("temperature_ressentie") if alert else None,
                "humidite": alert.get("humidite") if alert else None,
                "indice_uv": alert.get("indice_uv") if alert else None,
                "vitesse_vent": alert.get("vitesse_vent") if alert else None,
                "alerte_type": alert.get("type") if alert else None,
                "alerte_niveau": alert.get("niveau") if alert else None,
                "alerte_active": int(alert is not None),
                "nb_coupures_24h": len(recent_24h),
                "nb_coupures_7j": len(recent_7d),
                "nb_coupures_30j": len(recent_30d),
                "jours_depuis_coupure": days_since,
                "nb_signalements_valides_7j": len(previous_signalements),
                "target": int(not future_coupures.empty),
            }
            rows.append(row)

    dataset = pd.DataFrame(rows).sort_values(["reference_at", "zone_id"]).reset_index(drop=True)
    metadata = {
        "source_tables": ["zones", "coupures", "alertes_meteo", "signalements_coupures"],
        "read_only": True,
        "reference_granularity": "one row per zone per day at midnight",
        "target_definition": "coupures.zone_id matches and date_debut in [reference_at, reference_at + 24 hours[",
        "as_of": as_of.isoformat(sep=" "),
        "reference_min": dataset["reference_at"].min(),
        "reference_max": dataset["reference_at"].max(),
        "rows": int(len(dataset)),
        "zones": int(dataset["zone_id"].nunique()),
        "target_distribution": dataset["target"].value_counts().sort_index().astype(int).to_dict(),
        "missing_by_column": dataset.isna().sum().astype(int).to_dict(),
        "source_counts": {name: len(rows) for name, rows in sources.items()},
        "limitations": [
            "Aucune observation synthetique n'a ete ajoutee.",
            "Les donnees de demonstration eventuellement presentes dans MySQL ne sont pas distinguees automatiquement des donnees reelles.",
            "La meteo est absente lorsqu'aucune alerte connue et active n'existait a reference_at; elle n'est pas inventee.",
            "Le statut valide des signalements n'est pas historise; cette variable peut donc rester imparfaite.",
            "Les dates et heures MySQL sont exportees sans fuseau explicite, comme dans le schema existant.",
        ],
    }
    return dataset, metadata


def write_outputs(dataset: pd.DataFrame, metadata: dict[str, Any], output: Path) -> None:
    output.parent.mkdir(parents=True, exist_ok=True)
    dataset.to_csv(output, index=False, encoding="utf-8", quoting=csv.QUOTE_MINIMAL)
    metadata_path = output.with_suffix(".metadata.json")
    metadata_path.write_text(json.dumps(metadata, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Dataset exporte: {output}")
    print(f"Metadonnees: {metadata_path}")
    print(json.dumps({key: metadata[key] for key in ("rows", "zones", "target_distribution", "missing_by_column")}, ensure_ascii=False, indent=2))


def main() -> int:
    args = parse_args()
    as_of = parse_datetime(args.as_of, "as-of") if args.as_of else datetime.now()
    date_from = parse_datetime(args.date_from, "from") if args.date_from else None
    date_to = parse_datetime(args.date_to, "to") if args.date_to else None
    connection = None
    try:
        connection = connect(args.env_file)
        try:
            with connection.cursor() as cursor:
                cursor.execute("START TRANSACTION READ ONLY")
            sources = read_source_tables(connection)
            dataset, metadata = build_dataset(sources, date_from, date_to, as_of)
            connection.rollback()
        except Exception:
            connection.rollback()
            raise
        write_outputs(dataset, metadata, args.output)
        return 0
    except (ExportError, OSError, pymysql.MySQLError) as error:
        print(f"Export impossible: {error}", file=sys.stderr)
        return 2
    finally:
        if connection is not None:
            connection.close()


if __name__ == "__main__":
    sys.exit(main())

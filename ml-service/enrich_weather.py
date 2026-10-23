from __future__ import annotations

import argparse
import json
import time
from pathlib import Path
from urllib.parse import urlencode
from urllib.request import Request, urlopen

import pandas as pd

ARCHIVE_URL = "https://archive-api.open-meteo.com/v1/archive"
WEATHER_COLUMNS = {
    "temperature_min": "temperature_2m_min",
    "temperature_max": "temperature_2m_max",
    "temperature_ressentie": "apparent_temperature_max",
    "humidite": "relative_humidity_2m_mean",
    "vitesse_vent": "wind_speed_10m_max",
    "indice_uv": "uv_index_max",
}


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Enrich a HeatAlert CSV with observed historical Open-Meteo weather."
    )
    parser.add_argument("--input", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--cache-dir", type=Path, default=Path("data") / "weather_cache")
    parser.add_argument("--delay", type=float, default=0.2)
    return parser.parse_args()


def fetch_weather(
    latitude: float,
    longitude: float,
    start_date: str,
    end_date: str,
    cache_path: Path,
) -> dict[str, dict[str, float | None]]:
    if cache_path.exists():
        return json.loads(cache_path.read_text(encoding="utf-8"))

    query = urlencode(
        {
            "latitude": latitude,
            "longitude": longitude,
            "start_date": start_date,
            "end_date": end_date,
            "daily": ",".join(WEATHER_COLUMNS.values()),
            "timezone": "auto",
        }
    )
    request = Request(f"{ARCHIVE_URL}?{query}", headers={"User-Agent": "HeatAlert-ML/1.0"})
    with urlopen(request, timeout=30) as response:
        payload = json.load(response)

    dates = payload.get("daily", {}).get("time", [])
    result: dict[str, dict[str, float | None]] = {}
    for index, date in enumerate(dates):
        result[date] = {}
        for output_column, api_column in WEATHER_COLUMNS.items():
            values = payload.get("daily", {}).get(api_column, [])
            result[date][output_column] = values[index] if index < len(values) else None

    cache_path.parent.mkdir(parents=True, exist_ok=True)
    cache_path.write_text(json.dumps(result, indent=2), encoding="utf-8")
    return result


def main() -> int:
    args = parse_args()
    frame = pd.read_csv(args.input)
    required = {"zone_id", "latitude", "longitude", "reference_at"}
    missing = sorted(required - set(frame.columns))
    if missing:
        raise SystemExit(f"Colonnes absentes: {', '.join(missing)}")

    dates = pd.to_datetime(frame["reference_at"], errors="coerce", utc=True)
    if dates.isna().any():
        raise SystemExit("reference_at contient des dates invalides.")
    frame["reference_date"] = dates.dt.strftime("%Y-%m-%d")
    start_date = frame["reference_date"].min()
    end_date = frame["reference_date"].max()
    for output_column in WEATHER_COLUMNS:
        if output_column not in frame:
            frame[output_column] = pd.NA
        else:
            frame[output_column] = frame[output_column].astype(object)

    for zone_id, zone_rows in frame.groupby("zone_id", sort=True):
        latitude = zone_rows["latitude"].dropna().iloc[0]
        longitude = zone_rows["longitude"].dropna().iloc[0]
        cache_path = args.cache_dir / f"zone_{zone_id}_{latitude}_{longitude}_{start_date}_{end_date}.json"
        weather = fetch_weather(
            float(latitude), float(longitude), start_date, end_date, cache_path
        )
        for output_column in WEATHER_COLUMNS:
            frame.loc[zone_rows.index, output_column] = frame.loc[zone_rows.index].apply(
                lambda row: (
                    row[output_column]
                    if pd.notna(row[output_column])
                    else weather.get(row["reference_date"], {}).get(output_column)
                ),
                axis=1,
            )
        time.sleep(max(args.delay, 0))

    frame["weather_observation_source"] = "Open-Meteo Archive observed daily data"
    frame["weather_observed_after_event_warning"] = 1
    frame = frame.drop(columns=["reference_date"])
    args.output.parent.mkdir(parents=True, exist_ok=True)
    frame.to_csv(args.output, index=False)
    print(f"Dataset enrichi: {args.output}")
    print("Attention: ces valeurs sont des observations historiques, pas des previsions disponibles avant reference_at.")
    print(f"Lignes: {len(frame)}; valeurs manquantes apres enrichissement: {int(frame.isna().sum().sum())}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

<?php

namespace App\Services;

use App\Models\AlerteMeteo;
use App\Models\Coupure;
use App\Models\SignalementCoupure;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class HeatAlertMlService
{
    public function predictForZone(Zone $zone, ?Carbon $referenceAt = null): array
    {
        if ($zone->latitude === null || $zone->longitude === null) {
            throw new RuntimeException('La zone doit avoir une latitude et une longitude pour être prédite.');
        }

        $referenceAt ??= now();
        $payload = $this->buildPayload($zone, $referenceAt);
        $url = rtrim((string) config('services.heatalert_ml.url'), '/') . '/predict';
        $timeout = (int) config('services.heatalert_ml.timeout', 5);
        $request = Http::acceptJson()->timeout($timeout);

        if ($token = config('services.heatalert_ml.token')) {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->post($url, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('HeatAlert ML service unavailable', ['zone_id' => $zone->id]);
            throw new RuntimeException('Le service de prédiction est indisponible.', 0, $exception);
        }

        if ($response->failed()) {
            Log::warning('HeatAlert ML service returned an HTTP error', [
                'zone_id' => $zone->id,
                'status' => $response->status(),
                'detail' => $response->json('detail'),
            ]);
            throw new RuntimeException('Le service de prédiction a retourné une erreur.');
        }

        $result = $response->json();
        $validated = Validator::make($result, [
            'probability' => ['required', 'numeric', 'between:0,1'],
            'risk_level' => ['nullable', 'string'],
            'risk_thresholds_defined' => ['required', 'boolean'],
            'target_window_hours' => ['required', 'integer', 'in:24'],
            'target_window_start' => ['required', 'date'],
            'target_window_end_exclusive' => ['required', 'date', 'after:target_window_start'],
            'model_version' => ['required', 'string', 'max:255'],
            'warnings' => ['present', 'array'],
        ])->validate();

        // Calcul du créneau horaire estimé de la coupure (Surcharge / Délestage thermique)
        $estimatedOutage = $this->calculateEstimatedOutageWindow(
            (float) $validated['probability'],
            $referenceAt,
            $payload
        );

        return [
            'response' => array_merge($validated, $estimatedOutage),
            'input_snapshot' => $payload,
        ];
    }

    /**
     * Estime l'heure de début et de fin de coupure dans les 24h selon la probabilité et le profil thermique.
     * Les coupures de surcharge / délestage surviennent typiquement pendant le pic de chaleur (13h-17h).
     */
    public function calculateEstimatedOutageWindow(float $probability, Carbon $referenceAt, array $payload): array
    {
        // Seuil à partir duquel un créneau de coupure est estimé (>= 50%)
        if ($probability < 0.50) {
            return [
                'estimated_outage_start' => null,
                'estimated_outage_end' => null,
                'estimated_outage_cause' => null,
            ];
        }

        $now = $referenceAt->copy();
        $currentHour = (int) $now->format('H');

        // Heure de pic thermique typique en Tunisie : 13h30 à 17h00 (durée ~3h30)
        if ($currentHour < 13) {
            // Le pic est pour aujourd'hui en début d'après-midi
            $start = $now->copy()->setTime(13, 30, 0);
            $end   = $now->copy()->setTime(17, 0, 0);
        } elseif ($currentHour >= 13 && $currentHour < 17) {
            // On est en plein dans la période de pic thermique : risque imminent dans l'heure
            $start = $now->copy()->addMinutes(20)->roundMinute(15);
            $end   = $start->copy()->addHours(3)->addMinutes(30);
        } elseif ($currentHour >= 17 && $currentHour < 21) {
            // Pic de consommation vespéral (éclairage + clim après journée chaude)
            $start = $now->copy()->addMinutes(30)->roundMinute(15);
            $end   = $start->copy()->addHours(2);
        } else {
            // Nuit passée 21h : la prochaine fenêtre critique est demain après-midi à 13h30
            $start = $now->copy()->addDay()->setTime(13, 30, 0);
            $end   = $now->copy()->addDay()->setTime(17, 0, 0);
        }

        $tempMax = $payload['temperature_max'] ?? null;
        $cause = ($tempMax && $tempMax >= 38)
            ? 'Surcharge thermique & Pic de consommation'
            : 'Délestage préventif du réseau';

        return [
            'estimated_outage_start' => $start,
            'estimated_outage_end' => $end,
            'estimated_outage_cause' => $cause,
        ];
    }

    private function buildPayload(Zone $zone, Carbon $referenceAt): array
    {
        $alert = AlerteMeteo::query()
            ->where('zone_id', $zone->id)
            ->where('date_debut', '<=', $referenceAt)
            ->where(function ($query) use ($referenceAt) {
                $query->whereNull('date_fin')->orWhere('date_fin', '>', $referenceAt);
            })
            ->latest('date_debut')
            ->first();

        $history = Coupure::query()
            ->where('zone_id', $zone->id)
            ->where('date_debut', '<', $referenceAt);
        $lastCoupureAt = (clone $history)->max('date_debut');

        // 1. Priorité absolue : Météo réelle en direct via satellite Open-Meteo (basée sur GPS de la zone)
        $liveWeather = $this->fetchLiveWeather((float) $zone->latitude, (float) $zone->longitude);

        // 2. Si l'API Open-Meteo a répondu avec succès, on utilise ces vraies données en temps réel
        if (!is_null($liveWeather['temperature_max'])) {
            $weather = $liveWeather;
        } elseif ($alert) {
            // 3. Fallback secondaire : si l'API satellite est injoignable, on prend les valeurs de l'alerte
            $weather = [
                'temperature_min'       => $alert->temperature_min,
                'temperature_max'       => $alert->temperature_max,
                'temperature_ressentie' => $alert->temperature_ressentie,
                'humidite'              => $alert->humidite,
                'indice_uv'             => $alert->indice_uv,
                'vitesse_vent'          => $alert->vitesse_vent,
            ];
        } else {
            $weather = $liveWeather;
        }

        return [
            'zone_id'                    => $zone->id,
            'ville'                      => $zone->ville,
            'gouvernorat'                => $zone->gouvernorat ?? '',
            'latitude'                   => $zone->latitude !== null ? (float) $zone->latitude : null,
            'longitude'                  => $zone->longitude !== null ? (float) $zone->longitude : null,
            'zone_active'                => (bool) $zone->actif,
            'reference_at'               => $referenceAt->toIso8601String(),
            'temperature_min'            => $weather['temperature_min'],
            'temperature_max'            => $weather['temperature_max'],
            'temperature_ressentie'      => $weather['temperature_ressentie'],
            'humidite'                   => $weather['humidite'],
            'indice_uv'                  => $weather['indice_uv'],
            'vitesse_vent'               => $weather['vitesse_vent'],
            'alerte_type'                => $alert?->type,
            'alerte_niveau'              => $alert?->niveau,
            'alerte_active'              => $alert !== null,
            'nb_coupures_24h'            => (clone $history)->where('date_debut', '>=', $referenceAt->copy()->subDay())->count(),
            'nb_coupures_7j'             => (clone $history)->where('date_debut', '>=', $referenceAt->copy()->subDays(7))->count(),
            'nb_coupures_30j'            => (clone $history)->where('date_debut', '>=', $referenceAt->copy()->subDays(30))->count(),
            'jours_depuis_coupure'       => $lastCoupureAt ? abs($referenceAt->diffInSeconds(Carbon::parse($lastCoupureAt))) / 86400 : null,
            'nb_signalements_valides_7j' => SignalementCoupure::query()
                ->where('statut_validation', 'valide')
                ->where('date_signalement', '<', $referenceAt)
                ->where('date_signalement', '>=', $referenceAt->copy()->subDays(7))
                ->whereHas('coupure', fn ($query) => $query->where('zone_id', $zone->id))
                ->count(),
        ];
    }

    /**
     * Récupère la météo actuelle depuis Open-Meteo forecast (données horaires).
     * Retourne null pour chaque variable si l'appel échoue (non bloquant).
     *
     * @return array{temperature_min:float|null,temperature_max:float|null,temperature_ressentie:float|null,humidite:float|null,indice_uv:float|null,vitesse_vent:float|null}
     */
    private function fetchLiveWeather(float $latitude, float $longitude): array
    {
        $blank = [
            'temperature_min'       => null,
            'temperature_max'       => null,
            'temperature_ressentie' => null,
            'humidite'              => null,
            'indice_uv'             => null,
            'vitesse_vent'          => null,
        ];

        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude'        => $latitude,
                'longitude'       => $longitude,
                'current'         => implode(',', [
                    'temperature_2m',
                    'apparent_temperature',
                    'relative_humidity_2m',
                    'wind_speed_10m',
                    'uv_index',
                ]),
                'daily'           => implode(',', [
                    'temperature_2m_min',
                    'temperature_2m_max',
                ]),
                'timezone'        => 'auto',
                'forecast_days'   => 1,
            ]);

            if ($response->failed()) {
                Log::info('Open-Meteo forecast unavailable', ['status' => $response->status()]);
                return $blank;
            }

            $current = $response->json('current', []);
            $daily   = $response->json('daily',   []);

            return [
                'temperature_min'       => isset($daily['temperature_2m_min'][0])   ? (float) $daily['temperature_2m_min'][0]   : null,
                'temperature_max'       => isset($daily['temperature_2m_max'][0])   ? (float) $daily['temperature_2m_max'][0]   : null,
                'temperature_ressentie' => isset($current['apparent_temperature'])   ? (float) $current['apparent_temperature']   : null,
                'humidite'              => isset($current['relative_humidity_2m'])   ? (float) $current['relative_humidity_2m']   : null,
                'indice_uv'             => isset($current['uv_index'])               ? (float) $current['uv_index']               : null,
                'vitesse_vent'          => isset($current['wind_speed_10m'])         ? (float) $current['wind_speed_10m']         : null,
            ];
        } catch (\Throwable $e) {
            Log::info('Open-Meteo fetch failed', ['error' => $e->getMessage()]);
            return $blank;
        }
    }
}

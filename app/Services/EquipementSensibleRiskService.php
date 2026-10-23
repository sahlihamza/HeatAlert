<?php

namespace App\Services;

use App\Models\EquipementSensible;
use App\Models\EquipementSensiblePrediction;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class EquipementSensibleRiskService
{
    public function predict(EquipementSensible $equipement, ?Carbon $referenceAt = null): EquipementSensiblePrediction
    {
        $equipement->loadMissing('zone');
        // Une même heure doit produire une seule prédiction active par équipement.
        $referenceAt = ($referenceAt ?? now())->copy()->startOfHour();
        $payload = $this->buildPayload($equipement, $referenceAt);
        $url = rtrim((string) config('services.heatalert_ml.url'), '/') . '/predict/equipements';
        $request = Http::acceptJson()->timeout((int) config('services.heatalert_ml.timeout', 5));

        if ($token = config('services.heatalert_ml.token')) {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->post($url, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('HeatAlert equipment risk service unavailable', [
                'equipement_id' => $equipement->id,
            ]);
            throw new RuntimeException('Le service de risque des équipements est indisponible.', 0, $exception);
        }

        if ($response->failed()) {
            Log::warning('HeatAlert equipment risk service returned an HTTP error', [
                'equipement_id' => $equipement->id,
                'status' => $response->status(),
                'detail' => $response->json('detail'),
            ]);
            throw new RuntimeException('Le service de risque des équipements a retourné une erreur.');
        }

        $responseData = $response->json();
        if ((int) ($responseData['equipement_id'] ?? 0) !== (int) $equipement->id) {
            Log::warning('HeatAlert equipment risk service returned a mismatched equipment id', [
                'expected_equipement_id' => $equipement->id,
                'received_equipement_id' => $responseData['equipement_id'] ?? null,
            ]);
            throw new RuntimeException('Le service de risque a retourné un équipement différent.');
        }

        $validated = Validator::make($responseData, [
            'equipement_id' => ['required', 'integer'],
            'probability' => ['required', 'numeric', 'between:0,1'],
            'risk_level' => ['required', 'in:low,medium,high'],
            'target_window_hours' => ['required', 'integer', 'in:24'],
            'target_window_start' => ['required', 'date'],
            'target_window_end_exclusive' => ['required', 'date', 'after:target_window_start'],
            'model_version' => ['required', 'string', 'max:255'],
            'factors' => ['required', 'array'],
            'warnings' => ['present', 'array'],
        ])->validate();

        return $equipement->riskPredictions()->updateOrCreate(
            [
                'target_window_start' => \Carbon\Carbon::parse($validated['target_window_start'])->utc(),
            ],
            [
                'probability'                 => $validated['probability'],
                'risk_level'                  => $validated['risk_level'],
                'target_window_hours'         => $validated['target_window_hours'],
                'target_window_end_exclusive' => \Carbon\Carbon::parse($validated['target_window_end_exclusive'])->utc(),
                'model_version'               => $validated['model_version'],
                'factors'                     => $validated['factors'],
                'warnings'                    => $validated['warnings'],
                'input_snapshot'              => $payload,
            ]
        );

    }

    private function buildPayload(EquipementSensible $equipement, Carbon $referenceAt): array
    {
        $zone = $equipement->zone;
        $alert = $zone?->alertes()
            ->where('statut', 'active')
            ->where('date_debut', '<=', $referenceAt)
            ->where(fn ($query) => $query->whereNull('date_fin')->orWhere('date_fin', '>', $referenceAt))
            ->latest('date_debut')
            ->first();
        $weather = $this->fetchLiveWeather($zone?->latitude, $zone?->longitude);
        $coupures = $zone?->coupures()
            ->where('date_debut', '>=', $referenceAt->copy()->subDays(7))
            ->get(['date_debut', 'date_fin']) ?? collect();

        $recent = $coupures->filter(fn ($coupure) => $coupure->date_debut?->gte($referenceAt->copy()->subDay()));
        $durations = $coupures->map(function ($coupure) use ($referenceAt) {
            $end = $coupure->date_fin ?? $referenceAt;
            return max(0, $coupure->date_debut?->diffInMinutes($end) ?? 0);
        });

        return [
            'equipement_id' => $equipement->id,
            'type' => $equipement->type,
            'niveau_sensibilite' => $equipement->niveau_sensibilite,
            'actif' => (bool) $equipement->actif,
            'zone_active' => (bool) ($zone?->actif ?? false),
            'reference_at' => $referenceAt->toIso8601String(),
            'nb_coupures_24h' => $recent->count(),
            'nb_coupures_7j' => $coupures->count(),
            'duree_moyenne_coupure_7j' => round((float) ($durations->avg() ?? 0), 2),
            'temperature_max' => $weather['temperature_max'] ?? $alert?->temperature_max,
            'temperature_ressentie' => $weather['temperature_ressentie'] ?? $alert?->temperature_ressentie,
            'alerte_active' => $alert !== null,
        ];
    }

    /**
     * @return array{temperature_max: float|null, temperature_ressentie: float|null}
     */
    private function fetchLiveWeather(?float $latitude, ?float $longitude): array
    {
        if ($latitude === null || $longitude === null) {
            return ['temperature_max' => null, 'temperature_ressentie' => null];
        }

        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'current' => 'apparent_temperature',
                'daily' => 'temperature_2m_max',
                'timezone' => 'auto',
                'forecast_days' => 1,
            ]);

            if ($response->successful()) {
                $dailyMax = $response->json('daily.temperature_2m_max.0');
                $apparentTemperature = $response->json('current.apparent_temperature');

                return [
                    'temperature_max' => $dailyMax !== null ? (float) $dailyMax : null,
                    'temperature_ressentie' => $apparentTemperature !== null
                        ? (float) $apparentTemperature : null,
                ];
            }
        } catch (\Throwable $exception) {
            Log::info('Météo équipement indisponible, fallback alerte utilisé.', [
                'error' => $exception->getMessage(),
            ]);
        }

        return ['temperature_max' => null, 'temperature_ressentie' => null];
    }
}

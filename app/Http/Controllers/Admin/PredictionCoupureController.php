<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePredictionCoupureRequest;
use App\Models\PredictionCoupure;
use App\Models\Zone;
use App\Services\HeatAlertMlService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PredictionCoupureController extends Controller
{
    public function __construct(private HeatAlertMlService $mlService) {}

    public function index(Request $request): View
    {
        $riskFilter = $request->input('risk');

        $zonesQuery = Zone::query()
            ->with('latestPrediction')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($zoneQuery) use ($search) {
                    $zoneQuery->where('nom', 'like', "%{$search}%")
                        ->orWhere('ville', 'like', "%{$search}%")
                        ->orWhere('gouvernorat', 'like', "%{$search}%");
                });
            })
            ->when($riskFilter && in_array($riskFilter, ['high', 'medium', 'low']), function ($query) use ($riskFilter) {
                $query->whereHas('latestPrediction', function ($pq) use ($riskFilter) {
                    $pq->where('risk_level', $riskFilter);
                });
            });

        // Tri par risque critique en premier si demandé ou par défaut
        if ($request->input('sort') === 'risk_desc') {
            $zonesQuery->leftJoin('prediction_coupures', function ($join) {
                $join->on('zones.id', '=', 'prediction_coupures.zone_id')
                    ->whereRaw('prediction_coupures.id = (SELECT p2.id FROM prediction_coupures p2 WHERE p2.zone_id = zones.id ORDER BY p2.reference_at DESC, p2.id DESC LIMIT 1)');
            })
            ->select('zones.*')
            ->orderByRaw('prediction_coupures.probability IS NULL, prediction_coupures.probability DESC');
        } else {
            $zonesQuery->orderBy('gouvernorat')->orderBy('ville');
        }

        $zones = $zonesQuery->paginate(12)->withQueryString();

        // Compteurs rapides par niveau de risque
        $counts = [
            'all'    => Zone::count(),
            'high'   => Zone::whereHas('latestPrediction', fn($q) => $q->where('risk_level', 'high'))->count(),
            'medium' => Zone::whereHas('latestPrediction', fn($q) => $q->where('risk_level', 'medium'))->count(),
            'low'    => Zone::whereHas('latestPrediction', fn($q) => $q->where('risk_level', 'low'))->count(),
        ];

        // Zones avec coordonnées pour la carte interactive de risque
        $mapZones = Zone::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('latestPrediction')
            ->get()
            ->map(function ($z) {
                $p = $z->latestPrediction;
                return [
                    'id' => $z->id,
                    'nom' => $z->nom,
                    'ville' => $z->ville,
                    'gouvernorat' => $z->gouvernorat,
                    'lat' => (float) $z->latitude,
                    'lng' => (float) $z->longitude,
                    'probability' => $p ? (float) $p->probability : null,
                    'risk_level' => $p ? $p->risk_level : 'none',
                    'outage_start' => $p?->estimated_outage_start?->format('d/m à H:i'),
                    'outage_end' => $p?->estimated_outage_end?->format('H:i'),
                    'cause' => $p?->estimated_outage_cause,
                    'url' => route('admin.zones.show', $z),
                ];
            });

        return view('admin.predictions.index', [
            'zones' => $zones,
            'mapZones' => $mapZones,
            'staleAfterMinutes' => 24 * 60,
            'currentRisk' => $riskFilter,
            'counts' => $counts,
        ]);
    }

    public function store(StorePredictionCoupureRequest $request, Zone $zone): JsonResponse
    {
        try {
            $referenceAt = $request->filled('reference_at')
                ? Carbon::parse($request->validated('reference_at'))
                : now();
            $prediction = $this->mlService->predictForZone($zone, $referenceAt);
            $result = $prediction['response'];

            $stored = PredictionCoupure::create([
                'zone_id' => $zone->id,
                'reference_at' => $referenceAt,
                'target_window_start' => $result['target_window_start'],
                'target_window_end' => $result['target_window_end_exclusive'],
                'estimated_outage_start' => $result['estimated_outage_start'] ?? null,
                'estimated_outage_end' => $result['estimated_outage_end'] ?? null,
                'estimated_outage_cause' => $result['estimated_outage_cause'] ?? null,
                'probability' => $result['probability'],
                'risk_level' => $result['risk_level'],
                'risk_thresholds_defined' => $result['risk_thresholds_defined'],
                'model_version' => $result['model_version'],
                'warnings' => $result['warnings'],
                'input_snapshot' => $prediction['input_snapshot'],
            ]);

            session()->flash('success', "Prédiction actualisée avec succès pour {$zone->nom}.");

            return response()->json([
                'success' => true,
                'message' => "Prédiction actualisée avec succès pour {$zone->nom}.",
                'prediction' => $stored->load('zone'),
            ], 201);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 503);
        }
    }

    /**
     * Lance ou actualise la prédiction ML pour toutes les zones actives ayant des coordonnées.
     */
    public function storeAll(Request $request): JsonResponse
    {
        $zones = Zone::query()
            ->where('actif', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($zones->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune zone active avec coordonnées GPS disponibles.',
            ], 422);
        }

        $referenceAt = $request->filled('reference_at')
            ? Carbon::parse($request->input('reference_at'))
            : now();

        $successCount = 0;
        $errors = [];

        foreach ($zones as $zone) {
            try {
                $prediction = $this->mlService->predictForZone($zone, $referenceAt);
                $result = $prediction['response'];

                PredictionCoupure::create([
                    'zone_id' => $zone->id,
                    'reference_at' => $referenceAt,
                    'target_window_start' => $result['target_window_start'],
                    'target_window_end' => $result['target_window_end_exclusive'],
                    'estimated_outage_start' => $result['estimated_outage_start'] ?? null,
                    'estimated_outage_end' => $result['estimated_outage_end'] ?? null,
                    'estimated_outage_cause' => $result['estimated_outage_cause'] ?? null,
                    'probability' => $result['probability'],
                    'risk_level' => $result['risk_level'],
                    'risk_thresholds_defined' => $result['risk_thresholds_defined'],
                    'model_version' => $result['model_version'],
                    'warnings' => $result['warnings'],
                    'input_snapshot' => $prediction['input_snapshot'],
                ]);

                $successCount++;
            } catch (\Throwable $e) {
                $errors[] = $zone->nom . ' : ' . $e->getMessage();
            }
        }

        $msg = "Prédictions actualisées avec succès pour {$successCount} sur {$zones->count()} zone(s).";
        session()->flash('success', $msg);

        return response()->json([
            'success' => $successCount > 0,
            'success_count' => $successCount,
            'total_zones' => $zones->count(),
            'message' => $msg,
            'errors' => $errors,
        ]);
    }

    /**
     * Envoie une notification préventive (in-app et e-mail) aux résidents d'une zone avec risque élevé.
     */
    public function notifyResidents(Request $request, Zone $zone): JsonResponse
    {
        $prediction = $zone->latestPrediction;

        if (!$prediction) {
            return response()->json([
                'success' => false,
                'message' => "Aucune prédiction disponible pour la zone {$zone->nom}.",
            ], 404);
        }

        // Récupérer tous les utilisateurs résidents enregistrés dans cette zone
        $residents = $zone->users()->get();

        if ($residents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Aucun citoyen résident n'est enregistré dans la zone {$zone->nom} pour le moment.",
                'residents_count' => 0,
            ], 422);
        }

        $notification = new \App\Notifications\OutageRiskZoneAlertNotification($zone, $prediction);
        $notifiedCount = 0;

        foreach ($residents as $resident) {
            try {
                $resident->notify($notification);
                $notifiedCount++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Échec envoi notification citoyen #{$resident->id}: " . $e->getMessage());
            }
        }

        $msg = "Alerte préventive envoyée avec succès à {$notifiedCount} résident(s) de la zone {$zone->nom} (in-app et e-mail).";
        session()->flash('success', $msg);

        return response()->json([
            'success' => true,
            'message' => $msg,
            'residents_count' => $residents->count(),
            'notified_count' => $notifiedCount,
        ]);
    }
}

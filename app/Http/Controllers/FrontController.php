<?php

namespace App\Http\Controllers;

use App\Models\AlerteMeteo;
use App\Models\Conseil;
use App\Models\Coupure;
use App\Models\SignalementCoupure;
use App\Models\Zone;
use App\Http\Requests\StoreSignalementCoupureRequest;
use App\Services\NasaFirmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FrontController extends Controller
{
    /**
     * Page d'accueil du front pour les utilisateurs connectés.
     */
    public function index()
    {
        $alertesActives = AlerteMeteo::with('zone')
            ->where('statut', 'active')
            ->orderByRaw("FIELD(niveau, 'rouge','orange','jaune','vert')")
            ->take(6)
            ->get();

        $zonesActives = Zone::where('actif', true)
            ->withCount(['alertes' => fn($q) => $q->where('statut', 'active')])
            ->orderByDesc('alertes_count')
            ->take(6)
            ->get();

        $conseils = Conseil::where('actif', true)
            ->take(6)
            ->get();

        $stats = [
            'zones'   => Zone::where('actif', true)->count(),
            'alertes' => AlerteMeteo::where('statut', 'active')->count(),
            'conseils'=> Conseil::where('actif', true)->count(),
        ];

        return view('front.home', compact('alertesActives', 'zonesActives', 'conseils', 'stats'));
    }

    /**
     * Dashboard Météo Mondiale en direct avec l'API Open-Meteo.
     */
    public function globalWeather()
    {
        return view('front.weather.global');
    }

    /**
     * Liste des zones (front) avec recherche et filtres.
     */
    public function zones(Request $request)
    {
        $query = Zone::where('actif', true)
            ->withCount(['alertes', 'alertes as alertes_actives_count' => fn($q) => $q->where('statut', 'active')]);

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('ville', 'like', "%{$search}%")
                  ->orWhere('code_postal', 'like', "%{$search}%")
                  ->orWhere('gouvernorat', 'like', "%{$search}%");
            });
        }

        if ($gvt = $request->input('gouvernorat')) {
            $query->where('gouvernorat', $gvt);
        }

        if ($statutAlerte = $request->input('statut_alerte')) {
            if ($statutAlerte === 'avec_alertes') {
                $query->having('alertes_actives_count', '>', 0);
            } elseif ($statutAlerte === 'calme') {
                $query->having('alertes_actives_count', '=', 0);
            }
        }

        $zones = $query->orderBy('gouvernorat')->paginate(9)->appends($request->query());

        $gouvernorats = Zone::where('actif', true)
            ->whereNotNull('gouvernorat')
            ->distinct()
            ->orderBy('gouvernorat')
            ->pluck('gouvernorat');

        return view('front.zones.index', compact('zones', 'gouvernorats'));
    }

    /**
     * Détails d'une zone (front).
     */
    public function zoneShow(Zone $zone)
    {
        $zone->loadCount(['alertes', 'alertes as alertes_actives_count' => fn($q) => $q->where('statut', 'active')]);
        $alertes = $zone->alertes()->with('zone')
            ->orderByRaw("FIELD(statut,'active','brouillon','terminee','annulee')")
            ->orderByRaw("FIELD(niveau,'rouge','orange','jaune','vert')")
            ->paginate(8);

        $niveauxData = [
            'rouge'  => $zone->alertes()->where('niveau', 'rouge')->count(),
            'orange' => $zone->alertes()->where('niveau', 'orange')->count(),
            'jaune'  => $zone->alertes()->where('niveau', 'jaune')->count(),
            'vert'   => $zone->alertes()->where('niveau', 'vert')->count(),
        ];

        // Dernière prédiction ML disponible pour cette zone
        $latestPrediction = $zone->latestPrediction;

        return view('front.zones.show', compact('zone', 'alertes', 'niveauxData', 'latestPrediction'));
    }

    /**
     * Liste des alertes (front) avec recherche et filtres.
     */
    public function alertes(Request $request)
    {
        $query = AlerteMeteo::with('zone');

        // Filtre Statut : 'active' par défaut, ou selon sélection
        $selectedStatut = $request->input('statut', 'active');
        if ($selectedStatut !== 'tous' && $selectedStatut !== '') {
            $query->where('statut', $selectedStatut);
        }

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('zone', function($zq) use ($search) {
                      $zq->where('nom', 'like', "%{$search}%")
                         ->orWhere('gouvernorat', 'like', "%{$search}%");
                  });
            });
        }

        if ($niveau = $request->input('niveau')) {
            $query->where('niveau', $niveau);
        }

        if ($zoneId = $request->input('zone_id')) {
            $query->where('zone_id', $zoneId);
        }

        $alertes = $query->orderByRaw("FIELD(niveau,'rouge','orange','jaune','vert')")
            ->orderByDesc('created_at')
            ->paginate(6) // Paginer à 6 pour toujours avoir une pagination active et visible
            ->appends($request->query());

        $statsNiveaux = [
            'rouge'  => AlerteMeteo::where('statut', 'active')->where('niveau', 'rouge')->count(),
            'orange' => AlerteMeteo::where('statut', 'active')->where('niveau', 'orange')->count(),
            'jaune'  => AlerteMeteo::where('statut', 'active')->where('niveau', 'jaune')->count(),
            'vert'   => AlerteMeteo::where('statut', 'active')->where('niveau', 'vert')->count(),
        ];

        $zonesList = Zone::where('actif', true)->orderBy('nom')->get();

        return view('front.alertes.index', compact('alertes', 'statsNiveaux', 'zonesList'));
    }

    /**
     * Détails d'une alerte (front).
     */
    public function alerteShow(AlerteMeteo $alerte)
    {
        $alerte->load(['zone', 'conseils']);
        return view('front.alertes.show', compact('alerte'));
    }

    /**
     * Liste des conseils (front) avec recherche et filtres.
     */
    public function conseils(Request $request)
    {
        $query = Conseil::where('actif', true);

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('contenu', 'like', "%{$search}%");
            });
        }

        if ($categorie = $request->input('categorie')) {
            $query->where('categorie', $categorie);
        }

        if ($niveauCible = $request->input('niveau_cible')) {
            $query->where('niveau_alerte_cible', $niveauCible);
        }

        $conseils = $query->orderBy('categorie')
            ->paginate(6) // Paginer à 6 pour assurer une pagination active
            ->appends($request->query());

        $categories = Conseil::where('actif', true)
            ->select('categorie')
            ->whereNotNull('categorie')
            ->distinct()
            ->orderBy('categorie')
            ->pluck('categorie');

        return view('front.conseils.index', compact('conseils', 'categories'));
    }

    /**
     * Détails d'un conseil de santé (front).
     */
    public function conseilShow(Conseil $conseil)
    {
        $conseil->load(['alertes.zone']);

        $relatedConseils = Conseil::where('actif', true)
            ->where('id', '!=', $conseil->id)
            ->where('categorie', $conseil->categorie)
            ->take(3)
            ->get();

        return view('front.conseils.show', compact('conseil', 'relatedConseils'));
    }

    /**
     * Liste les coupures visibles par l'utilisateur ou les coupures en cours pour un visiteur.
     */
    public function coupures(Request $request): View
    {
        Coupure::actualiserStatuts();

        $query = Coupure::with(['zone', 'interventions']);

        if (auth()->check()) {
            $query->where('zone_id', auth()->user()->zone_id);

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }
        } else {
            $query->where('statut', 'en_cours');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->input('tri', 'plus_proche') === 'plus_lointaine') {
            $query->orderByDesc('date_debut');
        } else {
            $query->orderBy('date_debut');
        }

        $coupures = $query->paginate(8)->withQueryString();

        $calendrierQuery = Coupure::with(['zone', 'interventions'])
            ->where('statut', 'prevue')
            ->whereBetween('date_debut', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->orderBy('date_debut');

        $calendrierEvents = $calendrierQuery->get()->map(function (Coupure $coupure): array {
            $couleur = match ($coupure->type) {
                'panne' => '#dc2626',
                'surcharge' => '#ea580c',
                default => '#d97706',
            };

            return [
                'id' => $coupure->id,
                'title' => 'Coupure ' . ucfirst($coupure->type) . ' — ' . ($coupure->zone?->nom ?? 'Zone inconnue'),
                'start' => $coupure->date_debut?->toIso8601String(),
                'end' => $coupure->retablissement_estime?->toIso8601String(),
                'extendedProps' => [
                    'zone' => $coupure->zone?->nom . ' (' . ($coupure->zone?->ville ?? '—') . ')',
                ],
                'url' => route('front.coupures.show', $coupure),
                'backgroundColor' => $couleur,
                'borderColor' => $couleur,
            ];
        })->values();

        $userZone = null;
        $zonePrediction = null;
        if (auth()->check() && auth()->user()->zone_id) {
            $userZone = Zone::with('latestPrediction')->find(auth()->user()->zone_id);
            $zonePrediction = $userZone?->latestPrediction;
        }

        // Cartographie nationale du risque de coupure pour les citoyens
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
                    'url' => route('front.zones.show', $z),
                ];
            });

        return view('front.coupures.index', [
            'coupures' => $coupures,
            'types' => Coupure::TYPES,
            'statuts' => Coupure::STATUTS,
            'calendrierEvents' => $calendrierEvents,
            'userZone' => $userZone,
            'zonePrediction' => $zonePrediction,
            'mapZones' => $mapZones,
        ]);
    }

    /**
     * Affiche une coupure accessible par l'utilisateur courant.
     */
    public function coupureShow(Coupure $coupure): View
    {
        Coupure::actualiserStatuts();

        $coupure->load(['zone', 'interventions'])
            ->loadCount(['signalements as signalements_valides_count' => function ($query) {
                $query->where('statut_validation', 'valide');
            }]);

        return view('front.coupures.show', compact('coupure'));
    }

    /**
     * Affiche le formulaire de signalement pour la zone de l'utilisateur.
     */
    public function createSignalement(Request $request): View
    {
        Coupure::actualiserStatuts();

        $user = auth()->user();
        $selectedCoupureId = $request->query('coupure_id');
        $selectedCoupure = null;

        if ($selectedCoupureId) {
            $selectedCoupure = Coupure::with('zone')->find($selectedCoupureId);
        }

        $query = Coupure::with('zone');
        if ($user && $user->zone_id) {
            $query->where('zone_id', $user->zone_id);
        }

        $coupures = $query->orderByDesc('date_debut')->get();

        if ($selectedCoupure && ! $coupures->contains('id', $selectedCoupure->id)) {
            $coupures->prepend($selectedCoupure);
        }

        return view('front.coupures.signaler', compact('coupures', 'selectedCoupure'));
    }

    /**
     * Enregistre un signalement créé par un habitant.
     */
    public function storeSignalement(StoreSignalementCoupureRequest $request): RedirectResponse
    {
        Coupure::actualiserStatuts();

        $data = $request->validated();
        $user = $request->user();

        if (! empty($data['coupure_id'])) {
            $coupure = Coupure::findOrFail($data['coupure_id']);

            abort_if($user->zone_id && $coupure->zone_id !== $user->zone_id && ! $user->isAdmin(), 403, 'Cette coupure ne concerne pas votre zone.');
            abort_if($coupure->statut === 'terminee', 422, 'Cette coupure est déjà terminée.');

            $signalementsRecents = SignalementCoupure::query()
                ->where('coupure_id', $coupure->id)
                ->where('created_at', '>=', now()->subMinutes(30));

            if ((clone $signalementsRecents)->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'description' => 'Vous avez déjà signalé cette coupure au cours des 30 dernières minutes.',
                ]);
            }

            if ($user->zone_id && (clone $signalementsRecents)->whereHas('user', function ($userQuery) use ($user) {
                $userQuery->where('zone_id', $user->zone_id);
            })->exists()) {
                throw ValidationException::withMessages([
                    'description' => 'Un habitant de votre zone a déjà signalé cette coupure au cours des 30 dernières minutes.',
                ]);
            }
        }

        // ── Gestion photo : priorité au widget Cloudinary, fallback upload serveur ──
        if ($request->filled('photo_cloudinary_url') && $request->filled('photo_cloudinary_id')) {
            // Photo déjà uploadée via le Cloudinary Widget côté client
            // On stocke le public_id pour pouvoir la supprimer/transformer plus tard
            $data['photo'] = $request->input('photo_cloudinary_id');
        } elseif ($request->hasFile('photo')) {
            // Fallback : upload serveur via CloudinaryService
            try {
                $cloudinary = app(\App\Services\CloudinaryService::class);
                $result     = $cloudinary->upload($request->file('photo'), 'signalements');
                $data['photo'] = $result['public_id'];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Cloudinary upload failed', ['error' => $e->getMessage()]);
                return back()->withInput()->withErrors(['photo' => "L'upload de la photo a échoué. Veuillez réessayer."]);
            }
        }
        // Ne pas passer ces champs au modèle
        unset($data['photo_cloudinary_url'], $data['photo_cloudinary_id']);

        $data['user_id'] = $user->id;
        $data['statut_validation'] = 'en_attente';

        SignalementCoupure::create($data);

        return redirect()->route('front.coupures.index')
            ->with('success', 'Votre signalement a été envoyé et sera vérifié par un administrateur.');
    }

    /**
     * Page de surveillance et détection des départs de feux via l'API NASA FIRMS.
     */
    public function firesIndex(Request $request, NasaFirmsService $firmsService): View
    {
        $zones = Zone::where('actif', true)
            ->orderBy('gouvernorat')
            ->orderBy('nom')
            ->get();

        $selectedZoneId = $request->query('zone_id');
        $selectedZone   = $selectedZoneId ? Zone::find($selectedZoneId) : null;

        // Si l'utilisateur est authentifié et a une zone rattachée
        if (!$selectedZone && auth()->check() && auth()->user()->zone_id) {
            $selectedZone = Zone::find(auth()->user()->zone_id);
        }

        // Zone par défaut : Jendouba (zone forestière à haut risque d'incendie) ou première zone
        if (!$selectedZone) {
            $selectedZone = $zones->firstWhere('gouvernorat', 'Jendouba') ?? $zones->first();
        }

        $sources           = NasaFirmsService::SOURCES;
        $hasConfiguredKey  = $firmsService->hasConfiguredKey();

        return view('front.fires.index', compact(
            'zones',
            'selectedZone',
            'sources',
            'hasConfiguredKey'
        ));
    }

    /**
     * API JSON proxy pour l'API NASA FIRMS avec calcul de proximité et métriques.
     */
    public function firesApi(Request $request, NasaFirmsService $firmsService): JsonResponse
    {
        $lat      = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng      = $request->filled('lng') ? (float) $request->input('lng') : null;
        $radius   = $request->input('radius', 50); // 10, 25, 50, 100, ou 'all'
        $days     = (int) $request->input('days', 1);
        $source   = (string) $request->input('source', 'VIIRS_SNPP_NRT');
        $mapKey   = $request->filled('map_key') ? (string) $request->input('map_key') : null;
        $zoneId   = $request->input('zone_id');

        // Si zone_id est fourni mais pas lat/lng
        if ($zoneId && ($lat === null || $lng === null)) {
            $zone = Zone::find($zoneId);
            if ($zone && $zone->latitude && $zone->longitude) {
                $lat = (float) $zone->latitude;
                $lng = (float) $zone->longitude;
            }
        }

        $data = $firmsService->getHotspots(
            $lat,
            $lng,
            $radius,
            $days,
            $source,
            $mapKey,
            'TUN'
        );

        // Ajout des infos de géolocalisation demandée pour le frontend
        $data['queried_location'] = [
            'lat'    => $lat,
            'lng'    => $lng,
            'radius' => $radius,
        ];

        return response()->json($data);
    }

    /**
     * Prédiction ML de propagation du feu dans les 24h via le microservice LightGBM.
     */
    public function firesPredictSpread(Request $request, NasaFirmsService $firmsService): JsonResponse
    {
        $hotspots = $request->input('hotspots');
        $cells    = $request->input('cells', []);
        $weather  = $request->input('weather', []);

        // Si aucun hotspot fourni, les récupérer depuis le service FIRMS
        if (empty($hotspots) && empty($cells)) {
            $lat    = $request->filled('lat') ? (float) $request->input('lat') : null;
            $lng    = $request->filled('lng') ? (float) $request->input('lng') : null;
            $radius = $request->input('radius', 50);
            $days   = (int) $request->input('days', 1);
            $source = (string) $request->input('source', 'VIIRS_SNPP_NRT');
            $mapKey = $request->filled('map_key') ? (string) $request->input('map_key') : null;

            $firmsData = $firmsService->getHotspots($lat, $lng, $radius, $days, $source, $mapKey, 'TUN');
            $hotspots  = $firmsData['hotspots'] ?? [];
        }

        if (empty($hotspots) && empty($cells)) {
            return response()->json([
                'success'          => false,
                'message'          => 'Aucun foyer actif détecté pour calculer la propagation.',
                'total_cells'      => 0,
                'predictions'      => [],
                'threatened_zones' => [],
                'summary'          => ['critique' => 0, 'eleve' => 0, 'modere' => 0, 'faible' => 0],
            ]);
        }

        $mlUrl   = rtrim((string) config('services.heatalert_ml.url', 'http://127.0.0.1:8001'), '/') . '/predict/feu';
        $timeout = (int) config('services.heatalert_ml.timeout', 8);

        try {
            $response = Http::timeout($timeout)->post($mlUrl, [
                'cells'           => $cells,
                'active_hotspots' => $hotspots,
                'weather'         => empty($weather) ? null : (array) $weather,
            ]);

            if ($response->failed()) {
                Log::warning('ML fire spread returned error: ' . $response->status(), ['body' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Le service ML de propagation a renvoyé une erreur.',
                ], 502);
            }

            $mlData      = $response->json();
            $predictions = $mlData['predictions'] ?? [];

            // Identifier les zones HeatAlert menacées (dans un rayon de 12 km des cellules critiques/élevées)
            $zones           = Zone::where('actif', true)->whereNotNull('latitude')->whereNotNull('longitude')->get();
            $threatenedZones = [];

            foreach ($predictions as &$pred) {
                if (in_array($pred['niveau'], ['critique', 'eleve', 'modere'])) {
                    foreach ($zones as $zone) {
                        $dist = $this->calculateHaversine(
                            (float) $pred['lat'],
                            (float) $pred['lon'],
                            (float) $zone->latitude,
                            (float) $zone->longitude
                        );
                        if ($dist <= 12.0) {
                            $zoneKey = $zone->id;
                            if (!isset($threatenedZones[$zoneKey])) {
                                $threatenedZones[$zoneKey] = [
                                    'id'                       => $zone->id,
                                    'nom'                      => $zone->nom,
                                    'ville'                    => $zone->ville,
                                    'gouvernorat'              => $zone->gouvernorat,
                                    'latitude'                 => (float) $zone->latitude,
                                    'longitude'                => (float) $zone->longitude,
                                    'max_risk_level'           => $pred['niveau'],
                                    'max_probability'          => $pred['probabilite'],
                                    'distance_to_risk_cell_km' => round($dist, 1),
                                ];
                            } else {
                                if ($pred['probabilite'] > $threatenedZones[$zoneKey]['max_probability']) {
                                    $threatenedZones[$zoneKey]['max_probability']          = $pred['probabilite'];
                                    $threatenedZones[$zoneKey]['max_risk_level']           = $pred['niveau'];
                                    $threatenedZones[$zoneKey]['distance_to_risk_cell_km'] = round($dist, 1);
                                }
                            }
                            $pred['threatened_zone'] = $zone->nom;
                        }
                    }
                }
            }
            unset($pred);

            // Trier les zones menacées par probabilité décroissante
            usort($threatenedZones, fn($a, $b) => $b['max_probability'] <=> $a['max_probability']);

            return response()->json([
                'success'          => true,
                'total_cells'      => count($predictions),
                'predictions'      => $predictions,
                'summary'          => $mlData['summary'] ?? [],
                'threatened_zones' => array_values($threatenedZones),
                'model_version'    => $mlData['model_version'] ?? 'lightgbm_fire_spread:v1',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur appel ML Fire Spread: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Impossible de joindre le microservice de prédiction ML (' . $e->getMessage() . ')',
            ], 503);
        }
    }

    private function calculateHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $dLat        = deg2rad($lat2 - $lat1);
        $dLon        = deg2rad($lon2 - $lon1);
        $a           = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
    }
}

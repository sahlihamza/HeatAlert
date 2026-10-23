<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupure;
use App\Services\StegRssService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contrôleur d'administration pour l'intégration STEG.
 *
 * Gère l'affichage du tableau de bord STEG et la synchronisation manuelle
 * des coupures officielles depuis steg.com.tn.
 */
class StegController extends Controller
{
    public function __construct(protected StegRssService $stegService)
    {
    }

    /**
     * Affiche le tableau de bord STEG avec les coupures détectées et importées.
     */
    public function index(): View
    {
        // Récupérer les données STEG depuis le cache (ou fetch si absent)
        $stegData = $this->stegService->fetchCoupures();

        $rawItems = collect($stegData['items'] ?? []);

        // Convertir les dates Carbon pour la vue
        $items = $rawItems->map(function ($item) {
            return array_merge($item, [
                'date_debut' => $item['date_debut'] instanceof \Carbon\Carbon
                    ? $item['date_debut']
                    : (isset($item['date_debut']) ? \Carbon\Carbon::parse($item['date_debut']) : null),
                'date_fin' => $item['date_fin'] instanceof \Carbon\Carbon
                    ? $item['date_fin']
                    : (isset($item['date_fin']) ? \Carbon\Carbon::parse($item['date_fin']) : null),
            ]);
        });

        // Coupures STEG déjà importées en base
        $stegCoupures = Coupure::with('zone')
            ->whereNotNull('steg_ref')
            ->latest()
            ->paginate(10);

        $stegCount = Coupure::whereNotNull('steg_ref')->count();

        // Références déjà importées (pour l'indicateur dans le tableau)
        $importedRefs = Coupure::whereNotNull('steg_ref')->pluck('steg_ref')->toArray();

        return view('admin.steg.index', [
            'items'        => $items,
            'totalItems'   => $items->count(),
            'source'       => $stegData['source'] ?? 'inconnu',
            'fetchedAt'    => $stegData['fetched_at'] ?? null,
            'stegCoupures' => $stegCoupures,
            'stegCount'    => $stegCount,
            'importedRefs' => $importedRefs,
        ]);
    }

    /**
     * Déclenche une synchronisation manuelle depuis l'interface admin.
     * Vide le cache avant de fetcher pour avoir les données fraîches.
     */
    public function sync(Request $request): RedirectResponse
    {
        // Forcer un rafraîchissement du cache
        $this->stegService->clearCache();

        $result = $this->stegService->syncToDatabase();

        return redirect()
            ->route('admin.steg.index')
            ->with('steg_result', $result)
            ->with(
                'success',
                "✅ Synchronisation STEG : {$result['created']} coupure(s) importée(s), {$result['skipped']} ignorée(s)."
            );
    }
}


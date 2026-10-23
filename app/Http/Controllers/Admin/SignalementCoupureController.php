<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SignalementCoupure;
use App\Models\Zone;
use App\Services\SignalementAnomalyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SignalementCoupureController extends Controller
{
    /**
     * Liste paginée des signalements avec leurs relations.
     */
    public function index(Request $request, SignalementAnomalyService $anomalyService): View
    {
        $query = SignalementCoupure::with(['user', 'coupure.zone'])
            ->orderByRaw("FIELD(statut_validation, 'en_attente', 'valide', 'rejete')")
            ->orderByDesc('date_signalement');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('coupure.zone', function ($z) use ($search) {
                      $z->where('nom', 'like', "%{$search}%")
                        ->orWhere('ville', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('statut_validation')) {
            $query->where('statut_validation', $request->input('statut_validation'));
        }

        if ($request->filled('zone_id')) {
            $query->whereHas('coupure', function ($coupureQuery) use ($request) {
                $coupureQuery->where('zone_id', $request->input('zone_id'));
            });
        }

        $signalements = $query->paginate(10)->withQueryString();
        $signalements->getCollection()->transform(function (SignalementCoupure $signalement) use ($anomalyService) {
            $signalement->anomaly_analysis = $anomalyService->analyze($signalement);

            return $signalement;
        });
        $zones = Zone::query()->orderBy('gouvernorat')->orderBy('nom')->get();

        return view('admin.signalements.index', compact('signalements', 'zones'));
    }

    /**
     * Affiche le détail d'un signalement.
     */
    public function show(SignalementCoupure $signalement, SignalementAnomalyService $anomalyService): View
    {
        $signalement->load(['user', 'coupure.zone']);
        $anomalyAnalysis = $anomalyService->analyze($signalement);

        return view('admin.signalements.show', compact('signalement', 'anomalyAnalysis'));
    }

    /**
     * Valide un signalement.
     * Si le signalement n'est rattaché à aucune coupure existante,
     * crée automatiquement une nouvelle coupure "en cours" avec les détails nécessaires.
     */
    public function valider(SignalementCoupure $signalement): RedirectResponse
    {
        $signalement->loadMissing(['user', 'coupure']);

        if (! $signalement->coupure_id) {
            $zoneId = $signalement->user?->zone_id
                ?? Zone::where('actif', true)->value('id')
                ?? Zone::value('id');

            $nouvelleCoupure = \App\Models\Coupure::create([
                'type'        => 'panne',
                'statut'      => 'en_cours',
                'date_debut'  => $signalement->date_signalement ?? now(),
                'date_fin'    => null,
                'cause'       => 'Coupure issue du signalement citoyen #' . $signalement->id . ' : ' . \Illuminate\Support\Str::limit($signalement->description, 180),
                'zone_id'     => $zoneId,
            ]);

            $signalement->coupure_id = $nouvelleCoupure->id;
        }

        $signalement->statut_validation = 'valide';
        $signalement->save();

        $msg = isset($nouvelleCoupure)
            ? 'Signalement validé avec succès ! Une nouvelle coupure en cours a été automatiquement créée et liée.'
            : 'Le signalement a été validé avec succès.';

        return redirect()->route('admin.signalements.index')
            ->with('success', $msg);
    }

    /**
     * Rejette un signalement.
     */
    public function rejeter(SignalementCoupure $signalement): RedirectResponse
    {
        $signalement->update(['statut_validation' => 'rejete']);

        return redirect()->route('admin.signalements.index')
            ->with('success', 'Le signalement a été rejeté.');
    }

    /**
     * Supprime un signalement.
     */
    public function destroy(SignalementCoupure $signalement): RedirectResponse
    {
        $signalement->delete();

        return redirect()->route('admin.signalements.index')
            ->with('deleted', 'Le signalement a été supprimé.');
    }
}

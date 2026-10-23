<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFrontEquipementSensibleRequest;
use App\Http\Requests\UpdateFrontEquipementSensibleRequest;
use App\Models\EquipementSensible;
use App\Services\EquipementSensibleService;
use App\Services\Module4NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Throwable;

class FrontEquipementSensibleController extends Controller
{
    public function __construct(
        private EquipementSensibleService $service,
        private Module4NotificationService $notificationService,
        private \App\Services\EquipementSensibleRiskService $riskService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'niveau_sensibilite', 'actif']);
        $equipements = $this->service->getForUserPaginated($request->user(), $filters);

        // Prédiction lazy : déclencher la prédiction pour les équipements sans prédiction existante.
        foreach ($equipements as $equipement) {
            if ($equipement->latestRiskPrediction === null) {
                try {
                    $this->riskService->predict($equipement);
                    $equipement->unsetRelation('latestRiskPrediction');
                    $equipement->load('latestRiskPrediction');
                } catch (Throwable $exception) {
                    Log::warning('Prédiction lazy équipement indisponible.', [
                        'equipement_id' => $equipement->id,
                        'error'         => $exception->getMessage(),
                    ]);
                }
            }
        }

        return view('front.equipements_sensibles.index', [
            'equipements' => $equipements,
            'niveaux'     => EquipementSensible::NIVEAUX,
            'filters'     => $filters,
        ]);
    }

    public function create(): View
    {
        return view('front.equipements_sensibles.create', [
            'niveaux' => EquipementSensible::NIVEAUX,
            'types' => EquipementSensible::TYPES,
        ]);
    }

    public function store(StoreFrontEquipementSensibleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['actif'] = $request->boolean('actif', true);
        $equipement = $this->service->create($data);
        $this->notificationService->generateForUser($request->user());
        $redirect = redirect()->route('front.equipements_sensibles.index')
            ->with('success', 'Équipement ajouté à votre préparation.');

        try {
            $this->riskService->predict($equipement);
        } catch (Throwable $exception) {
            Log::warning('Prédiction équipement indisponible après création.', [
                'equipement_id' => $equipement->id,
                'error' => $exception->getMessage(),
            ]);
            $redirect->with('risk_warning', 'Équipement ajouté, mais la prédiction est temporairement indisponible.');
        }

        return $redirect;
    }

    public function edit(Request $request, EquipementSensible $equipementSensible): View
    {
        abort_unless($equipementSensible->user_id === $request->user()->id, 403);
        return view('front.equipements_sensibles.edit', [
            'equipement' => $equipementSensible,
            'niveaux' => EquipementSensible::NIVEAUX,
            'types' => EquipementSensible::TYPES,
        ]);
    }

    public function update(UpdateFrontEquipementSensibleRequest $request, EquipementSensible $equipementSensible): RedirectResponse
    {
        abort_unless($equipementSensible->user_id === $request->user()->id, 403);
        $data = $request->validated();
        $data['actif'] = $request->boolean('actif');
        $this->service->update($equipementSensible, $data);
        $this->notificationService->generateForUser($request->user());
        $redirect = redirect()->route('front.equipements_sensibles.index')
            ->with('success', 'Équipement mis à jour.');

        try {
            $this->riskService->predict($equipementSensible);
        } catch (Throwable $exception) {
            Log::warning('Prédiction équipement indisponible après mise à jour.', [
                'equipement_id' => $equipementSensible->id,
                'error' => $exception->getMessage(),
            ]);
            $redirect->with('risk_warning', 'Équipement mis à jour, mais la prédiction est temporairement indisponible.');
        }

        return $redirect;
    }

    public function destroy(Request $request, EquipementSensible $equipementSensible): RedirectResponse
    {
        abort_unless($equipementSensible->user_id === $request->user()->id, 403);
        $this->service->delete($equipementSensible);
        return back()->with('deleted', 'Équipement supprimé de votre préparation.');
    }
}

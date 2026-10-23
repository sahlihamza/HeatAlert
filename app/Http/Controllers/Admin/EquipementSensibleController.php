<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipementSensibleRequest;
use App\Http\Requests\UpdateEquipementSensibleRequest;
use App\Models\EquipementSensible;
use App\Services\EquipementSensibleService;
use App\Services\ZoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipementSensibleController extends Controller
{
    public function __construct(
        private EquipementSensibleService $service,
        private ZoneService $zoneService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'zone_id', 'type', 'niveau_sensibilite', 'actif']);
        $equipements = $this->service->getPaginated($filters, 12);
        $zones = $this->zoneService->getActiveForSelect();
        $niveaux = EquipementSensible::NIVEAUX;
        $types = EquipementSensible::TYPES;

        return view('admin.equipements_sensibles.index', compact('equipements', 'zones', 'niveaux', 'types'));
    }

    public function create(): View
    {
        $zones = $this->zoneService->getActiveForSelect();
        $niveaux = EquipementSensible::NIVEAUX;
        $types = EquipementSensible::TYPES;

        return view('admin.equipements_sensibles.create', compact('zones', 'niveaux', 'types'));
    }

    public function store(StoreEquipementSensibleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['actif'] = $request->boolean('actif', true);

        $equipement = $this->service->create($data);

        $notifiedCount = $this->service->notifyUsersInSameZone($equipement, 'created');

        $message = 'Équipement sensible créé avec succès.';
        if ($notifiedCount > 0) {
            $message .= " Un e-mail d'information a été envoyé à {$notifiedCount} utilisateur(s) de cette zone.";
        }

        return redirect()->route('admin.equipements-sensibles.index')
            ->with('success', $message);
    }

    public function show(EquipementSensible $equipementSensible): View
    {
        $equipementSensible->load(['zone', 'user', 'module4Notifications']);
        return view('admin.equipements_sensibles.show', ['equipement' => $equipementSensible]);
    }

    public function edit(EquipementSensible $equipementSensible): View
    {
        $zones = $this->zoneService->getActiveForSelect();
        $niveaux = EquipementSensible::NIVEAUX;
        $types = EquipementSensible::TYPES;

        return view('admin.equipements_sensibles.edit', [
            'equipement' => $equipementSensible,
            'zones' => $zones,
            'niveaux' => $niveaux,
            'types' => $types,
        ]);
    }

    public function update(UpdateEquipementSensibleRequest $request, EquipementSensible $equipementSensible): RedirectResponse
    {
        $data = $request->validated();
        $data['actif'] = $request->boolean('actif');

        $this->service->update($equipementSensible, $data);
        $equipementSensible->refresh();

        $notifiedCount = $this->service->notifyUsersInSameZone($equipementSensible, 'updated');

        $message = 'Équipement sensible mis à jour avec succès.';
        if ($notifiedCount > 0) {
            $message .= " Un e-mail d'information a été envoyé à {$notifiedCount} utilisateur(s) de cette zone.";
        }

        return redirect()->route('admin.equipements-sensibles.index')
            ->with('success', $message);
    }

    public function destroy(EquipementSensible $equipementSensible): RedirectResponse
    {
        $nom = $equipementSensible->nom;
        $this->service->delete($equipementSensible);

        return redirect()->route('admin.equipements-sensibles.index')
            ->with('deleted', "L'équipement « {$nom} » a été supprimé.");
    }
}

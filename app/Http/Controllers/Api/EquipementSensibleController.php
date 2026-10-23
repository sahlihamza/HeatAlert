<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFrontEquipementSensibleRequest;
use App\Http\Requests\UpdateFrontEquipementSensibleRequest;
use App\Http\Resources\EquipementSensibleResource;
use App\Models\EquipementSensible;
use App\Services\EquipementSensibleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EquipementSensibleController extends Controller
{
    public function __construct(private EquipementSensibleService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'niveau_sensibilite' => ['nullable', 'in:' . implode(',', EquipementSensible::NIVEAUX)],
            'actif' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 8);
        unset($filters['per_page']);

        return EquipementSensibleResource::collection(
            $this->service->getForUserPaginated($request->user(), $filters, $perPage)
        );
    }

    public function store(StoreFrontEquipementSensibleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['actif'] = $request->boolean('actif');

        return (new EquipementSensibleResource($this->service->create($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, EquipementSensible $equipementSensible): EquipementSensibleResource
    {
        $this->ensureOwner($request, $equipementSensible);

        return new EquipementSensibleResource($equipementSensible);
    }

    public function update(
        UpdateFrontEquipementSensibleRequest $request,
        EquipementSensible $equipementSensible
    ): EquipementSensibleResource {
        $this->ensureOwner($request, $equipementSensible);
        $data = $request->validated();
        $data['actif'] = $request->boolean('actif');
        $this->service->update($equipementSensible, $data);

        return new EquipementSensibleResource($equipementSensible->refresh());
    }

    public function destroy(Request $request, EquipementSensible $equipementSensible): JsonResponse
    {
        $this->ensureOwner($request, $equipementSensible);
        $this->service->delete($equipementSensible);

        return response()->json([
            'message' => 'L’équipement a été supprimé de votre préparation.',
        ]);
    }

    private function ensureOwner(Request $request, EquipementSensible $equipementSensible): void
    {
        abort_unless($equipementSensible->user_id === $request->user()->id, 403);
    }
}

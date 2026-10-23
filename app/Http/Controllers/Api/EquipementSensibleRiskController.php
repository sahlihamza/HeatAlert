<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipementSensible;
use App\Services\EquipementSensibleRiskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipementSensibleRiskController extends Controller
{
    public function __construct(private EquipementSensibleRiskService $service) {}

    public function store(Request $request, EquipementSensible $equipementSensible): JsonResponse
    {
        abort_unless($equipementSensible->user_id === $request->user()->id, 403);

        $prediction = $this->service->predict($equipementSensible);

        return response()->json([
            'data' => $prediction->load('equipement'),
        ], 201);
    }
}

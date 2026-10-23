<?php

namespace App\Http\Controllers;

use App\Models\PredictionCoupure;
use App\Models\Zone;
use App\Services\VoiceAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VoiceAlertController extends Controller
{
    public function __construct(protected VoiceAlertService $voiceService)
    {
    }

    /**
     * Retourne le script textuel et l'URL audio pour une prédiction donnée.
     */
    public function getScript(Request $request, Zone $zone): JsonResponse
    {
        $prediction = $zone->latestPrediction;

        if (!$prediction) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune prédiction trouvée pour cette zone.',
            ], 404);
        }

        $lang = $request->query('lang', 'ar-TN');
        $script = $this->voiceService->generateScript($prediction, $lang);

        return response()->json([
            'success'   => true,
            'zone'      => $zone->nom ?? $zone->ville,
            'lang'      => $lang,
            'script'    => $script,
            'audio_url' => route('voice.stream', ['zone' => $zone->id, 'lang' => $lang]),
        ]);
    }

    /**
     * Stream le flux MP3 de l'alerte vocale pour la zone demandée.
     */
    public function streamAudio(Request $request, Zone $zone): Response
    {
        $prediction = $zone->latestPrediction;

        if (!$prediction) {
            return response('', 404);
        }

        $lang = $request->query('lang', 'ar-TN');
        $script = $this->voiceService->generateScript($prediction, $lang);

        return $this->voiceService->synthesize($script, $lang);
    }
}


<?php

namespace App\Services;

use App\Models\PredictionCoupure;
use App\Models\Zone;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de Synthèse Vocale et Bulletins d'Alerte en Dialecte Tunisien (Derja) & Français.
 *
 * Supporte :
 *  - Azure Cognitive Services Speech (ar-TN-ReemNeural / ar-TN-HediNeural)
 *  - Fallback 100% gratuit Google Translate TTS (sans clé API)
 */
class VoiceAlertService
{
    /**
     * Génère le script textuel d'alerte en dialecte tunisien (Derja) ou en français.
     */
    public function generateScript(PredictionCoupure $prediction, string $lang = 'ar-TN'): string
    {
        $zone = $prediction->zone;
        $zoneName = $zone ? ($zone->nom ?? $zone->ville) : 'منطقتكم';
        $probPct = round($prediction->probability * 100);

        $startHour = $prediction->estimated_outage_start ? $prediction->estimated_outage_start->format('H:i') : null;
        $endHour = $prediction->estimated_outage_end ? $prediction->estimated_outage_end->format('H:i') : null;

        if ($lang === 'ar-TN' || str_starts_with($lang, 'ar')) {
            return $this->buildDerjaScript($zoneName, $probPct, $startHour, $endHour);
        }

        return $this->buildFrenchScript($zoneName, $probPct, $startHour, $endHour);
    }

    /**
     * Script formulé en dialecte tunisien authentique (Derja).
     */
    protected function buildDerjaScript(string $zoneName, int $probPct, ?string $startHour, ?string $endHour): string
    {
        $text = "عسلامة، تنبيه من منصة هيت ألارت. ";
        $riskLabel = $probPct >= 70 ? 'خطر كبير' : ($probPct >= 40 ? 'خطر متوسط' : 'خطر ضعيف');
        $text .= "مستوى الخطر {$riskLabel}، واحتمال انقطاع التيّار الكهربائي في {$zoneName} هو {$probPct} في المائة. ";

        if ($startHour && $endHour) {
            $text .= "الانقطاع متوقع يبدا من السوايع {$startHour} و يرجع مع {$endHour}. ";
        } else {
            $text .= "الانقطاع متوقع في الأربعة و عشرين ساعة الجاية بسبب السخانة القوية. ";
        }

        if ($probPct >= 70) {
            $text .= "ننصحوكم تشرجيو تليفوناتكم توا، تسكروا الفريجيدار مليح، و تحضروا دبوزة ماء. ان شاء الله ربي يحفظكم.";
        } elseif ($probPct >= 40) {
            $text .= "ننصحوكم تتابعوا التحديثات، وتشحنوا تليفوناتكم وتاخذوا احتياطاتكم.";
        } else {
            $text .= "الخطر ضعيف، أما ردوا بالكم من السخانة وتابعوا التحديثات على المنصة.";
        }

        return $text;
    }

    /**
     * Script formulé en français.
     */
    protected function buildFrenchScript(string $zoneName, int $probPct, ?string $startHour, ?string $endHour): string
    {
        $text = "Bonjour, ici l'alerte météo HeatAlert. ";
        $riskLabel = $probPct >= 70 ? 'élevé' : ($probPct >= 40 ? 'modéré' : 'faible');
        $text .= "Le risque de coupure de courant est {$riskLabel} dans la zone de {$zoneName}, avec une probabilité de {$probPct} pour cent. ";

        if ($startHour && $endHour) {
            $text .= "Le créneau prévisionnel est estimé entre {$startHour} et {$endHour}. ";
        } else {
            $text .= "Cette interruption est estimée dans les prochaines 24 heures en raison des fortes chaleurs. ";
        }

        if ($probPct >= 70) {
            $text .= "Nous vous recommandons de recharger vos appareils, de garder les réfrigérateurs bien fermés et de prévoir de l'eau potable.";
        } elseif ($probPct >= 40) {
            $text .= "Nous vous conseillons de suivre les prochaines mises à jour et de recharger vos appareils par précaution.";
        } else {
            $text .= "Le risque reste faible. Restez attentif aux mises à jour et aux consignes locales.";
        }

        return $text;
    }

    /**
     * Synthétise le texte et retourne une réponse audio en streaming (audio/mpeg).
     */
    public function synthesize(string $text, string $lang = 'ar-TN'): Response
    {
        $azureKey = (string) config('services.azure_tts.key', '');
        $azureRegion = (string) config('services.azure_tts.region', 'eastus');

        // 1. Moteur Azure si configuré
        if (!empty(trim($azureKey))) {
            try {
                $voiceName = ($lang === 'ar-TN' || str_starts_with($lang, 'ar')) ? 'ar-TN-ReemNeural' : 'fr-FR-DeniseNeural';
                $locale = ($lang === 'ar-TN' || str_starts_with($lang, 'ar')) ? 'ar-TN' : 'fr-FR';
                return $this->azureTts($text, $locale, $voiceName, $azureKey, $azureRegion);
            } catch (\Throwable $e) {
                Log::warning('Azure TTS failed, falling back to Google TTS: ' . $e->getMessage());
            }
        }

        // 2. Moteur Fallback 100% gratuit Google TTS (sans clé)
        $googleLang = ($lang === 'ar-TN' || str_starts_with($lang, 'ar')) ? 'ar' : 'fr';
        return $this->googleTranslateTts($text, $googleLang);
    }

    /**
     * Appel à Azure Cognitive Services Speech REST API.
     */
    protected function azureTts(string $text, string $locale, string $voiceName, string $apiKey, string $region): Response
    {
        $text = mb_substr(strip_tags($text), 0, 500);
        $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');

        $ssml = <<<SSML
<speak version='1.0' xml:lang='{$locale}'>
  <voice xml:lang='{$locale}' xml:gender='Female' name='{$voiceName}'>
    <prosody rate='0%' pitch='0%'>{$escaped}</prosody>
  </voice>
</speak>
SSML;

        $endpoint = "https://{$region}.tts.speech.microsoft.com/cognitiveservices/v1";

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $apiKey,
            'Content-Type'              => 'application/ssml+xml',
            'X-Microsoft-OutputFormat'  => 'audio-16khz-128kbitrate-mono-mp3',
            'User-Agent'                => 'HeatAlert-TTS/1.0',
        ])->timeout(8)->withBody($ssml, 'application/ssml+xml')->post($endpoint);

        if (!$response->successful()) {
            throw new \RuntimeException('Azure TTS returned HTTP ' . $response->status());
        }

        return response($response->body(), 200, [
            'Content-Type'        => 'audio/mpeg',
            'Cache-Control'       => 'public, max-age=86400',
            'Content-Disposition' => 'inline; filename="alerte-derja.mp3"',
        ]);
    }

    /**
     * Moteur de secours gratuit Google Translate TTS.
     */
    protected function googleTranslateTts(string $text, string $lang): Response
    {
        $text = mb_substr(strip_tags($text), 0, 200);
        $encoded = rawurlencode($text);
        $url = "https://translate.google.com/translate_tts?ie=UTF-8&tl={$lang}&client=tw-ob&q={$encoded}";

        try {
            $resp = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer'    => 'https://translate.google.com/',
            ])->timeout(8)->get($url);

            if ($resp->successful() && strlen($resp->body()) > 200) {
                return response($resp->body(), 200, [
                    'Content-Type'        => 'audio/mpeg',
                    'Cache-Control'       => 'public, max-age=86400',
                    'Content-Disposition' => 'inline; filename="alerte-vocal.mp3"',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Google TTS failed: ' . $e->getMessage());
        }

        return response('', 204);
    }
}


<?php

namespace App\Services;

use App\Models\SignalementCoupure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Score explicable pour prioriser les signalements inhabituels en modération.
 * Ce score est une aide au tri et ne prend aucune décision automatiquement.
 */
class SignalementAnomalyService
{
    public function analyze(SignalementCoupure $signalement): array
    {
        $zoneId = $signalement->coupure?->zone_id ?? $signalement->user?->zone_id;

        if (! $zoneId || ! $signalement->date_signalement) {
            return [
                'score' => null,
                'level' => 'unavailable',
                'label' => 'Analyse indisponible',
                'reasons' => ['Zone ou date du signalement indisponible.'],
            ];
        }

        $date = $signalement->date_signalement;
        $sameZone = static function (Builder $query) use ($zoneId): void {
            $query->where(function (Builder $zoneQuery) use ($zoneId): void {
                $zoneQuery->whereHas('coupure', fn (Builder $coupureQuery) => $coupureQuery->where('zone_id', $zoneId))
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('zone_id', $zoneId));
            });
        };

        $nearbyReports = SignalementCoupure::query()
            ->whereBetween('date_signalement', [$date->copy()->subMinutes(7), $date->copy()->addMinutes(7)])
            ->where($sameZone);
        $reportsInWindow = (clone $nearbyReports)->count();

        $similarReports = SignalementCoupure::query()
            ->where('id', '!=', $signalement->id)
            ->whereBetween('date_signalement', [$date->copy()->subDay(), $date->copy()->addDay()])
            ->where($sameZone)
            ->orderBy('date_signalement')
            ->limit(200)
            ->get(['id', 'description']);

        $score = 0;
        $reasons = [];
        $normalizedDescription = $this->normalize($signalement->description);
        $bestSimilarity = 0.0;
        $exactDuplicate = false;

        foreach ($similarReports as $candidate) {
            $candidateDescription = $this->normalize($candidate->description);
            if ($candidateDescription === '' || $normalizedDescription === '') {
                continue;
            }

            if ($candidateDescription === $normalizedDescription) {
                $exactDuplicate = true;
                break;
            }

            $bestSimilarity = max($bestSimilarity, $this->tokenSimilarity($normalizedDescription, $candidateDescription));
        }

        if ($exactDuplicate) {
            $score = max($score, 85);
            $reasons[] = 'Description identique à un autre signalement de cette zone reçu dans les dernières 24 heures.';
        } elseif ($bestSimilarity >= 0.75) {
            $score = max($score, 60);
            $reasons[] = 'Description très similaire à un autre signalement de cette zone reçu dans les dernières 24 heures.';
        }

        if ($reportsInWindow >= 5) {
            $score = max($score, 70);
            $reasons[] = "Pic d'activité : {$reportsInWindow} signalements dans cette zone sur une fenêtre de 15 minutes.";
        } elseif ($reportsInWindow >= 3) {
            $score = max($score, 45);
            $reasons[] = "Activité inhabituelle : {$reportsInWindow} signalements dans cette zone sur une fenêtre de 15 minutes.";
        }

        if ($reasons === []) {
            $reasons[] = 'Aucun doublon ou pic inhabituel détecté selon les règles actuelles.';
        }

        $level = $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low');
        $label = match ($level) {
            'high' => 'À vérifier en priorité',
            'medium' => 'Vérification conseillée',
            default => 'Aucune anomalie détectée',
        };

        return [
            'score' => $score,
            'level' => $level,
            'label' => $label,
            'reasons' => $reasons,
        ];
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function tokenSimilarity(string $first, string $second): float
    {
        $firstTokens = array_values(array_unique(array_filter(explode(' ', $first))));
        $secondTokens = array_values(array_unique(array_filter(explode(' ', $second))));
        $union = array_unique(array_merge($firstTokens, $secondTokens));

        if ($union === []) {
            return 0.0;
        }

        return count(array_intersect($firstTokens, $secondTokens)) / count($union);
    }
}

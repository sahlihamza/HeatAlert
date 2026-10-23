<?php

namespace App\Console\Commands;

use App\Models\EquipementSensible;
use App\Services\EquipementSensibleRiskService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshEquipementSensibleRisksCommand extends Command
{
    protected $signature = 'equipements:refresh-risks
        {--all : Inclure les équipements désactivés}
        {--id= : Ne traiter qu’un équipement précis}';

    protected $description = 'Actualise la prédiction 24 h de chaque équipement sensible';

    public function handle(EquipementSensibleRiskService $riskService): int
    {
        $query = EquipementSensible::query()->with('zone');
        if (!$this->option('all')) {
            $query->where('actif', true);
        }
        if ($id = $this->option('id')) {
            $query->whereKey((int) $id);
        }

        $processed = 0;
        $failed = 0;
        $query->chunkById(50, function ($equipements) use ($riskService, &$processed, &$failed) {
            foreach ($equipements as $equipement) {
                try {
                    $riskService->predict($equipement);
                    $processed++;
                } catch (Throwable $exception) {
                    $failed++;
                    Log::warning('Actualisation du risque équipement échouée.', [
                        'equipement_id' => $equipement->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        });

        $this->info("Prédictions actualisées : {$processed}.");
        if ($failed > 0) {
            $this->warn("Échecs : {$failed}. Consultez storage/logs/laravel.log.");
        }

        return $failed > 0 && $processed === 0 ? self::FAILURE : self::SUCCESS;
    }
}

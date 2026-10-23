<?php

namespace App\Console\Commands;

use App\Models\Coupure;
use App\Models\PredictionCoupure;
use App\Models\Zone;
use App\Notifications\OutageRiskZoneAlertNotification;
use App\Services\HeatAlertMlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCheckPredictionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:auto-check {--threshold=0.75 : Seuil de probabilité pour déclencher les alertes (ex: 0.75 pour 75%)} {--notify : Notifier automatiquement les résidents par e-mail/in-app} {--auto-draft : Créer un brouillon de coupure préventive STEG}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatisation ML : Actualise la météo et prédit les coupures sur toutes les zones avec alerte automatique sur les zones critiques';

    public function __construct(private HeatAlertMlService $mlService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = (float) $this->option('threshold');
        $shouldNotify = (bool) $this->option('notify');
        $shouldAutoDraft = (bool) $this->option('auto-draft');

        $this->info("⚡ Lancement de l'actualisation prédictive automatique (seuil d'alerte : " . ($threshold * 100) . "%)...");

        $zones = Zone::query()
            ->where('actif', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($zones->isEmpty()) {
            $this->warn('Aucune zone active avec coordonnées GPS trouvée.');
            return self::SUCCESS;
        }

        $now = now();
        $criticalCount = 0;
        $successCount = 0;

        foreach ($zones as $zone) {
            try {
                $this->line("Calcul pour la zone : <fg=cyan>{$zone->nom}</> ({$zone->ville})...");
                $predictionData = $this->mlService->predictForZone($zone, $now);
                $res = $predictionData['response'];

                $prediction = PredictionCoupure::create([
                    'zone_id' => $zone->id,
                    'reference_at' => $now,
                    'target_window_start' => $res['target_window_start'],
                    'target_window_end' => $res['target_window_end_exclusive'],
                    'estimated_outage_start' => $res['estimated_outage_start'] ?? null,
                    'estimated_outage_end' => $res['estimated_outage_end'] ?? null,
                    'estimated_outage_cause' => $res['estimated_outage_cause'] ?? null,
                    'probability' => $res['probability'],
                    'risk_level' => $res['risk_level'],
                    'risk_thresholds_defined' => $res['risk_thresholds_defined'],
                    'model_version' => $res['model_version'],
                    'warnings' => $res['warnings'],
                    'input_snapshot' => $predictionData['input_snapshot'],
                ]);

                $successCount++;
                $probPct = round($prediction->probability * 100, 1);

                // Détection de risque critique
                if ($prediction->probability >= $threshold || $prediction->risk_level === 'high') {
                    $criticalCount++;
                    $this->warn("  ⚠️ ALERTE CRITIQUE : {$zone->nom} présente un risque de {$probPct}% !");

                    // 1. Notification automatique des résidents si activé
                    if ($shouldNotify) {
                        $residents = $zone->users()->get();
                        if ($residents->isNotEmpty()) {
                            $notification = new OutageRiskZoneAlertNotification($zone, $prediction);
                            $notifCount = 0;
                            foreach ($residents as $resident) {
                                try {
                                    $resident->notify($notification);
                                    $notifCount++;
                                } catch (\Throwable $ne) {
                                    Log::warning("Cron AutoCheck: erreur notif citoyen #{$resident->id}: {$ne->getMessage()}");
                                }
                            }
                            $this->info("  📢 {$notifCount} citoyen(s) notifié(s) (In-App & Email).");
                        }
                    }

                    // 2. Création d'une coupure prévisionnelle en statut 'prevue' si activé
                    if ($shouldAutoDraft && $prediction->estimated_outage_start) {
                        $debut = $prediction->estimated_outage_start;
                        $fin = $prediction->estimated_outage_end;

                        // Vérifier si une coupure identique n'a pas déjà été enregistrée pour cette zone
                        $alreadyExists = Coupure::where('zone_id', $zone->id)
                            ->where('date_debut', $debut)
                            ->exists();

                        if (!$alreadyExists) {
                            Coupure::create([
                                'zone_id' => $zone->id,
                                'type' => str_contains(strtolower($prediction->estimated_outage_cause ?? ''), 'surcharge') ? 'surcharge' : 'delestage',
                                'statut' => 'prevue',
                                'date_debut' => $debut,
                                'date_fin' => $fin,
                                'cause' => "Planification automatique STEG suite détection de forte surcharge thermique ({$probPct}% de risque)",
                            ]);
                            $this->info("  ⚡ Coupure préventive STEG automatiquement programmée pour {$zone->nom} ({$debut->format('d/m H:i')}).");
                        }
                    }
                } else {
                    $this->line("  ✓ Situation stable (risque: {$probPct}%).");
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ Erreur pour la zone {$zone->nom}: {$e->getMessage()}");
                Log::error("predictions:auto-check zone {$zone->id} failed: " . $e->getMessage());
            }
        }

        $summary = "Cron Terminé : {$successCount}/{$zones->count()} zones actualisées. {$criticalCount} zone(s) en risque critique détectée(s).";
        $this->info($summary);
        Log::info($summary);

        return self::SUCCESS;
    }
}


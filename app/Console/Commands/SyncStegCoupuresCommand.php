<?php

namespace App\Console\Commands;

use App\Models\Zone;
use App\Services\StegRssService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande Artisan : Synchronisation des coupures officielles STEG.
 *
 * Usage :
 *   php artisan steg:sync-coupures              → Synchronise et importe les coupures
 *   php artisan steg:sync-coupures --dry-run    → Affiche sans importer (aperçu)
 *   php artisan steg:sync-coupures --fresh      → Vide le cache avant de fetcher
 *   php artisan steg:sync-coupures --show-demo  → Affiche les données de démo
 *
 * Planification (routes/console.php) :
 *   Schedule::command('steg:sync-coupures')->everyTwoHours()
 */
class SyncStegCoupuresCommand extends Command
{
    protected $signature = 'steg:sync-coupures
                            {--dry-run : Affiche les coupures détectées sans les importer}
                            {--fresh : Vide le cache STEG avant de fetcher}
                            {--show-demo : Force l\'utilisation des données de démonstration}';

    protected $description = 'Synchronise les coupures programmées officielles STEG avec la base HeatAlert';

    public function __construct(protected StegRssService $stegService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('⚡ HeatAlert — Synchronisation STEG');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Vider le cache si demandé
        if ($this->option('fresh')) {
            $this->stegService->clearCache();
            $this->components->info('🗑️  Cache STEG vidé.');
        }

        $this->components->task('Fetching données STEG', function () use (&$data) {
            $data = $this->stegService->fetchCoupures();
            return true;
        });

        $source    = $data['source'] ?? 'inconnu';
        $fromCache = $data['from_cache'] ?? false;
        $fetchedAt = $data['fetched_at'] ?? now()->format('d/m/Y H:i');
        $items     = $data['items'] ?? [];

        $this->newLine();
        $this->line("  📡 Source     : <fg=cyan>{$source}</fg=cyan>");
        $this->line("  🕐 Récupéré   : <fg=cyan>{$fetchedAt}</fg=cyan>");
        $this->line("  💾 Cache      : <fg=cyan>" . ($fromCache ? 'Oui' : 'Non') . '</fg=cyan>');
        $this->line("  📋 Items      : <fg=yellow>" . count($items) . ' coupures détectées</fg=yellow>');
        $this->newLine();

        if (empty($items)) {
            $this->components->warn('Aucune coupure STEG détectée à synchroniser.');
            return Command::SUCCESS;
        }

        // Afficher le tableau des coupures détectées
        $tableRows = array_map(function ($item) {
            $zones = implode(', ', $item['zones_detectees'] ?? ['N/A']);
            $debut = $item['date_debut'] ? $item['date_debut']->format('d/m H:i') : '?';
            $fin   = $item['date_fin']   ? $item['date_fin']->format('H:i')        : '?';
            $conf  = ($item['confidence'] ?? 0) . '%';

            return [
                mb_substr($item['titre'] ?? '?', 0, 45),
                $item['type'] ?? '?',
                $zones ?: 'N/A',
                "{$debut} → {$fin}",
                $conf,
                $item['source'] ?? '?',
            ];
        }, $items);

        $this->table(
            ['Titre', 'Type', 'Zones détectées', 'Créneau', 'Confiance', 'Source'],
            $tableRows
        );

        // Mode dry-run : afficher seulement
        if ($this->option('dry-run')) {
            $this->newLine();
            $this->components->info('Mode --dry-run : aucun enregistrement effectué.');
            return Command::SUCCESS;
        }

        // Synchronisation en base
        $this->newLine();
        $result = null;
        $this->components->task('Synchronisation en base de données', function () use (&$result) {
            $result = $this->stegService->syncToDatabase();
            return true;
        });

        $this->newLine();
        $this->line("  ✅ Créées    : <fg=green>{$result['created']}</fg=green>");
        $this->line("  ⏭️  Ignorées  : <fg=yellow>{$result['skipped']}</fg=yellow> (déjà existantes)");

        if (!empty($result['matched'])) {
            $this->line("  🗺️  Zones     : <fg=cyan>" . implode(', ', $result['matched']) . '</fg=cyan>');
        }

        if (!empty($result['errors'])) {
            $this->newLine();
            $this->components->warn(count($result['errors']) . ' erreur(s) :');
            foreach ($result['errors'] as $err) {
                $this->line("  ❌ {$err}");
            }
        }

        $this->newLine();
        $this->components->success("Synchronisation STEG terminée. {$result['created']} coupure(s) importée(s).");

        Log::info('[StegSync] Sync terminée', $result);

        return Command::SUCCESS;
    }
}


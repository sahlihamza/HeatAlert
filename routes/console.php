<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planification automatique de la prédiction des coupures
// S'exécute automatiquement toutes les 6 heures avec notifications et planification préventive
Schedule::command('predictions:auto-check --threshold=0.70 --notify --auto-draft')
    ->everySixHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/predictions-auto-check.log'));

// Synchronisation des coupures officielles STEG toutes les 2 heures
// Importe automatiquement les interruptions programmées publiées sur steg.com.tn
Schedule::command('steg:sync-coupures')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/steg-sync.log'));

// Actualise les risques avec l'historique des coupures et la météo actuelle.
Schedule::command('equipements:refresh-risks')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/equipements-risk.log'));

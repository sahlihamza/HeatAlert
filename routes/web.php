<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AlerteMeteoController;
use App\Http\Controllers\Admin\ConseilController;
use App\Http\Controllers\Admin\CoupureController;
use App\Http\Controllers\Admin\PointFraicheurController;
use App\Http\Controllers\Admin\PredictionCoupureController;
use App\Http\Controllers\Admin\SignalementCoupureController;
use App\Http\Controllers\Admin\StegController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\FrontPointFraicheurController;
use App\Http\Controllers\FrontEquipementSensibleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Module4NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — HeatAlert
|--------------------------------------------------------------------------
*/

// ─── Chatbot IA HeatAlert (Gemini Flash + Knowledge Dynamique + Charts & Historique)
Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('chatbot.ask');
Route::get('/chatbot/stats', [ChatbotController::class, 'quickStats'])->name('chatbot.stats');
Route::get('/chatbot/conversations', [ChatbotController::class, 'conversations'])->name('chatbot.conversations');
Route::get('/chatbot/conversations/{id}', [ChatbotController::class, 'getConversation'])->name('chatbot.conversations.show');
Route::post('/chatbot/conversations/new', [ChatbotController::class, 'newConversation'])->name('chatbot.conversations.new');
Route::delete('/chatbot/conversations/{id}', [ChatbotController::class, 'deleteConversation'])->name('chatbot.conversations.delete');
Route::delete('/chatbot/conversations', [ChatbotController::class, 'clearAllConversations'])->name('chatbot.conversations.clear');
Route::get('/chatbot/tts', [ChatbotController::class, 'tts'])->name('chatbot.tts');

// ─── Alertes Vocales Synthétisées en Dialecte Tunisien (Derja & FR)
Route::get('/voice/script/{zone}', [\App\Http\Controllers\VoiceAlertController::class, 'getScript'])->name('voice.script');
Route::get('/voice/stream/{zone}', [\App\Http\Controllers\VoiceAlertController::class, 'streamAudio'])->name('voice.stream');

// ─── Page d'accueil publique (splash screen + présentation)
Route::get('/', [HomeController::class, 'index'])->name('home');

// ─── Route 'dashboard' pour compatibilité Breeze (redirige selon le rôle)
Route::get('/dashboard', function () {
    if (auth()->user()?->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('front.home');
})->middleware('auth')->name('dashboard');

// ─── Consultation publique des coupures
Route::get('/coupures', [FrontController::class, 'coupures'])->name('front.coupures.index');

// ─── Routes authentifiées — Front (utilisateurs ROLE_USER)
Route::middleware(['auth'])->group(function () {
    Route::get('/accueil', [FrontController::class, 'index'])->name('front.home');
    Route::get('/meteo-mondiale', [FrontController::class, 'globalWeather'])->name('front.weather.global');
    Route::get('/zones', [FrontController::class, 'zones'])->name('front.zones.index');
    Route::get('/zones/{zone}', [FrontController::class, 'zoneShow'])->name('front.zones.show');
    Route::get('/alertes', [FrontController::class, 'alertes'])->name('front.alertes.index');
    Route::get('/alertes/{alerte}', [FrontController::class, 'alerteShow'])->name('front.alertes.show');
    Route::get('/conseils', [FrontController::class, 'conseils'])->name('front.conseils.index');
    Route::get('/conseils/{conseil}', [FrontController::class, 'conseilShow'])->name('front.conseils.show');
    Route::get('/coupures/signaler', [FrontController::class, 'createSignalement'])->name('front.coupures.signaler');
    Route::post('/coupures/signaler', [FrontController::class, 'storeSignalement'])->name('front.coupures.signaler.store');
    Route::get('/coupures/{coupure}', [FrontController::class, 'coupureShow'])->name('front.coupures.show')->whereNumber('coupure');

    // ─── Points de Fraîcheur (front)
    Route::get('/points-fraicheur', [FrontPointFraicheurController::class, 'index'])->name('front.points_fraicheur.index');
    Route::get('/points-fraicheur/{pointFraicheur}', [FrontPointFraicheurController::class, 'show'])->name('front.points_fraicheur.show');
    Route::post('/points-fraicheur/{pointFraicheur}/avis', [FrontPointFraicheurController::class, 'storeAvis'])->name('front.avis_points.store');
    Route::put('/avis-points/{avisPoint}', [FrontPointFraicheurController::class, 'updateAvis'])->name('front.avis_points.update');
    Route::delete('/avis-points/{avisPoint}', [FrontPointFraicheurController::class, 'destroyAvis'])->name('front.avis_points.destroy');

    // ─── Détection des Départs de Feux (NASA FIRMS)
    Route::get('/feux-foret', [FrontController::class, 'firesIndex'])->name('front.fires.index');
    Route::get('/feux-foret/api', [FrontController::class, 'firesApi'])->name('front.fires.api');
    Route::post('/feux-foret/predict-spread', [FrontController::class, 'firesPredictSpread'])->name('front.fires.predict_spread');

    // ─── Profil Utilisateur (Détails & Modification)
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('mes-equipements', FrontEquipementSensibleController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['mes-equipements' => 'equipementSensible'])
        ->names('front.equipements_sensibles');
    Route::prefix('api')->name('api.')->group(function () {
        Route::apiResource('mes-equipements', \App\Http\Controllers\Api\EquipementSensibleController::class)
            ->parameters(['mes-equipements' => 'equipementSensible']);
        Route::post(
            'mes-equipements/{equipementSensible}/risk-prediction',
            [\App\Http\Controllers\Api\EquipementSensibleRiskController::class, 'store']
        )->name('mes-equipements.risk-prediction');
    });
    Route::get('/notifications', [NotificationController::class, 'index'])->name('front.notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('front.notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('front.notifications.read_all');
    Route::get('/mes-notifications-prevention', [Module4NotificationController::class, 'index'])->name('front.module4_notifications.index');
    Route::patch('/mes-notifications-prevention/{notification}/read', [Module4NotificationController::class, 'read'])->name('front.module4_notifications.read');
    Route::post('/mes-notifications-prevention/generate', [Module4NotificationController::class, 'generate'])->name('front.module4_notifications.generate');
});

// ─── Routes Admin — accessible uniquement aux ROLE_ADMIN
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/statistiques', [AdminController::class, 'statistiques'])->name('statistiques');

        // Profil & Sécurité Administrateur
        Route::get('/profil', [AdminController::class, 'profile'])->name('profile.edit');
        Route::patch('/profil', [AdminController::class, 'updateProfile'])->name('profile.update');

        // Gestion des Zones (avec recherche/filtre et page détails)
        Route::resource('zones', ZoneController::class);
        Route::post('zones/{zone}/prediction', [PredictionCoupureController::class, 'store'])
            ->name('zones.prediction');
        Route::get('predictions', [PredictionCoupureController::class, 'index'])
            ->name('predictions.index');
        Route::post('predictions/predict-all', [PredictionCoupureController::class, 'storeAll'])
            ->name('predictions.predict_all');
        Route::post('predictions/{zone}/notify-residents', [PredictionCoupureController::class, 'notifyResidents'])
            ->name('predictions.notify_residents');

        // Gestion des Alertes Météo
        Route::resource('alertes', AlerteMeteoController::class);
        Route::resource('equipements-sensibles', \App\Http\Controllers\Admin\EquipementSensibleController::class)
            ->parameters(['equipements-sensibles' => 'equipementSensible']);
        Route::get('notifications-prevention', [\App\Http\Controllers\Admin\Module4NotificationController::class, 'index'])->name('module4_notifications.index');
        Route::get('notifications-prevention/create', [\App\Http\Controllers\Admin\Module4NotificationController::class, 'create'])->name('module4_notifications.create');
        Route::post('notifications-prevention', [\App\Http\Controllers\Admin\Module4NotificationController::class, 'store'])->name('module4_notifications.store');

        // Gestion des Coupures électriques
        Route::get('coupures/{coupure}/interventions/create', [CoupureController::class, 'createIntervention'])
            ->name('coupures.interventions.create');
        Route::post('coupures/{coupure}/interventions', [CoupureController::class, 'storeIntervention'])
            ->name('coupures.interventions.store');
        Route::get('coupures/{coupure}/interventions/{intervention}/edit', [CoupureController::class, 'editIntervention'])
            ->name('coupures.interventions.edit');
        Route::put('coupures/{coupure}/interventions/{intervention}', [CoupureController::class, 'updateIntervention'])
            ->name('coupures.interventions.update');
        Route::delete('coupures/{coupure}/interventions/{intervention}', [CoupureController::class, 'destroyIntervention'])
            ->name('coupures.interventions.destroy');
        Route::resource('coupures', CoupureController::class);

        // Modération des signalements de coupures
        Route::resource('signalements', SignalementCoupureController::class)
            ->only(['index', 'show', 'destroy']);
        Route::patch('signalements/{signalement}/valider', [SignalementCoupureController::class, 'valider'])
            ->name('signalements.valider');
        Route::patch('signalements/{signalement}/rejeter', [SignalementCoupureController::class, 'rejeter'])
            ->name('signalements.rejeter');

        // Gestion des Conseils
        Route::resource('conseils', ConseilController::class);

        // Gestion des Points de Fraîcheur + supervision des avis
        Route::resource('points_fraicheur', PointFraicheurController::class);
        Route::get('avis-points', [PointFraicheurController::class, 'avisIndex'])->name('avis_points.index');
        Route::put('avis-points/{avisPoint}', [PointFraicheurController::class, 'avisUpdate'])->name('avis_points.update');
        Route::delete('avis-points/{avisPoint}', [PointFraicheurController::class, 'avisDestroy'])->name('avis_points.destroy');

        // Intégration STEG — Synchronisation des coupures officielles
        Route::get('steg', [StegController::class, 'index'])->name('steg.index');
        Route::post('steg/sync', [StegController::class, 'sync'])->name('steg.sync');
    });

// ─── Routes d'authentification (Breeze)
require __DIR__.'/auth.php';

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EquipementSensible extends Model
{
    use HasFactory;

    public const NIVEAUX = ['faible', 'moyen', 'eleve'];
    public const TYPES = ['frigo', 'medicament', 'ventilateur', 'appareil_medical'];

    public const TYPE_LABELS = [
        'frigo'            => 'Réfrigérateur',
        'medicament'       => 'Médicament à conserver',
        'ventilateur'      => 'Ventilateur / Climatiseur',
        'appareil_medical' => 'Appareil médical',
    ];

    public const TYPE_ICONS = [
        'frigo'            => 'fa-solid fa-snowflake',
        'medicament'       => 'fa-solid fa-pills',
        'ventilateur'      => 'fa-solid fa-fan',
        'appareil_medical' => 'fa-solid fa-heart-pulse',
    ];

    public const NIVEAU_LABELS = [
        'eleve'  => 'Élevé',
        'moyen'  => 'Moyen',
        'faible' => 'Faible',
    ];

    public const NIVEAU_BADGES = [
        'eleve'  => 'bg-danger text-white',
        'moyen'  => 'bg-warning text-dark',
        'faible' => 'bg-success text-white',
    ];

    protected $table = 'equipements_sensibles';

    protected $fillable = [
        'nom', 'type', 'description', 'adresse', 'zone_id', 'user_id',
        'niveau_sensibilite', 'actif',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type ?? ''));
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'fa-solid fa-microchip';
    }

    public function getNiveauLabelAttribute(): string
    {
        return self::NIVEAU_LABELS[$this->niveau_sensibilite] ?? ucfirst($this->niveau_sensibilite ?? '');
    }

    public function getNiveauBadgeAttribute(): string
    {
        return self::NIVEAU_BADGES[$this->niveau_sensibilite] ?? 'bg-secondary text-white';
    }

    protected $casts = ['actif' => 'boolean'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module4Notifications(): HasMany
    {
        return $this->hasMany(Module4Notification::class);
    }

    public function riskPredictions(): HasMany
    {
        return $this->hasMany(EquipementSensiblePrediction::class);
    }

    public function latestRiskPrediction(): HasOne
    {
        return $this->hasOne(EquipementSensiblePrediction::class)->latestOfMany('target_window_start');
    }
}

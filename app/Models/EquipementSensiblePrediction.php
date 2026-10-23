<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipementSensiblePrediction extends Model
{
    protected $table = 'equipement_sensible_predictions';

    protected $fillable = [
        'equipement_sensible_id',
        'probability',
        'risk_level',
        'target_window_hours',
        'target_window_start',
        'target_window_end_exclusive',
        'model_version',
        'factors',
        'warnings',
        'input_snapshot',
    ];

    protected $casts = [
        'probability' => 'float',
        'target_window_start' => 'datetime',
        'target_window_end_exclusive' => 'datetime',
        'factors' => 'array',
        'warnings' => 'array',
        'input_snapshot' => 'array',
    ];

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(EquipementSensible::class, 'equipement_sensible_id');
    }
}

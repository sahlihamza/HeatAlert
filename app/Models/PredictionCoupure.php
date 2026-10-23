<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionCoupure extends Model
{
    use HasFactory;

    protected $table = 'prediction_coupures';

    protected $fillable = [
        'zone_id',
        'reference_at',
        'target_window_start',
        'target_window_end',
        'estimated_outage_start',
        'estimated_outage_end',
        'estimated_outage_cause',
        'probability',
        'risk_level',
        'risk_thresholds_defined',
        'model_version',
        'warnings',
        'input_snapshot',
    ];

    protected $casts = [
        'reference_at' => 'datetime',
        'target_window_start' => 'datetime',
        'target_window_end' => 'datetime',
        'estimated_outage_start' => 'datetime',
        'estimated_outage_end' => 'datetime',
        'probability' => 'float',
        'risk_thresholds_defined' => 'boolean',
        'warnings' => 'array',
        'input_snapshot' => 'array',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Module4Notification extends Model
{
    protected $table = 'module4_notifications';

    protected $fillable = [
        'user_id', 'equipement_sensible_id', 'message', 'canal',
        'date_envoi', 'lue', 'deduplication_key',
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
        'lue' => 'boolean',
    ];

    public const CANAUX = ['email', 'sms', 'app'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(EquipementSensible::class, 'equipement_sensible_id');
    }
}

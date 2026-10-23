<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Coupure extends Model
{
    use HasFactory;

    public const TYPES = ['delestage', 'surcharge', 'panne'];
    public const STATUTS = ['prevue', 'en_cours', 'terminee'];

    protected $fillable = [
        'type',
        'statut',
        'date_debut',
        'date_fin',
        'cause',
        'zone_id',
        'steg_ref',  // Référence unique STEG (RSS/HTML/DEMO hash) pour déduplications
        'origin',    // Origine : manual | steg_rss | steg_html | steg_demo
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(SignalementCoupure::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public static function actualiserStatuts(): void
    {
        $maintenant = now();

        self::query()
            ->whereIn('statut', ['prevue', 'en_cours'])
            ->whereNotNull('date_fin')
            ->where('date_fin', '<=', $maintenant)
            ->update(['statut' => 'terminee']);

        self::query()
            ->whereIn('statut', ['prevue', 'en_cours'])
            ->where('date_debut', '<=', $maintenant)
            ->where(function ($query) use ($maintenant) {
                $query->whereNull('date_fin')
                    ->orWhere('date_fin', '>', $maintenant);
            })
            ->update(['statut' => 'en_cours']);

        self::query()
            ->whereIn('statut', ['en_cours', 'terminee'])
            ->where('date_debut', '>', $maintenant)
            ->update(['statut' => 'prevue']);
    }

    protected function retablissementEstime(): Attribute
    {
        return Attribute::get(function (): ?\Carbon\Carbon {
            $intervention = $this->relationLoaded('interventions')
                ? $this->interventions->sortByDesc('date_prevue')->first()
                : $this->interventions()->latest('date_prevue')->first();

            if ($intervention?->date_prevue) {
                return $intervention->date_prevue->copy()->addMinutes($intervention->duree_estimee_min);
            }

            return $this->date_fin;
        });
    }
}
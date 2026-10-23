<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_USER  = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    protected $fillable = [
        'nom', 'prenom', 'email', 'password', 'telephone',
        'role', 'date_inscription', 'zone_id', 'email_verified_at',
        // Vérification email à l'inscription
        'email_verification_code',
        'email_verification_expires_at',
        // 2FA TOTP (QR Code)
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at'              => 'datetime',
            'date_inscription'               => 'datetime',
            'password'                       => 'hashed',
            'email_verification_expires_at'  => 'datetime',
            'two_factor_enabled'             => 'boolean',
            'two_factor_expires_at'          => 'datetime',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(SignalementCoupure::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function avisPoints(): HasMany
    {
        return $this->hasMany(AvisPoint::class);
    }

    public function equipementsSensibles(): HasMany
    {
        return $this->hasMany(EquipementSensible::class);
    }

    public function module4Notifications(): HasMany
    {
        return $this->hasMany(Module4Notification::class);
    }

    public function chatbotConversations(): HasMany
    {
        return $this->hasMany(ChatbotConversation::class)->orderBy('updated_at', 'desc');
    }

    /**
     * Indique si le code de vérification email est encore valide.
     */
    public function isEmailVerificationCodeValid(string $code): bool
    {
        return $this->email_verification_code === $code
            && $this->email_verification_expires_at
            && $this->email_verification_expires_at->isFuture();
    }

    /**
     * Génère et stocke un nouveau code de vérification email (6 chiffres, 15 min).
     */
    public function generateEmailVerificationCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'email_verification_code'        => $code,
            'email_verification_expires_at'  => now()->addMinutes(15),
        ]);
        return $code;
    }
}
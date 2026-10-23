<?php

namespace App\Services;

use App\Models\EquipementSensible;
use App\Models\Module4Notification;
use App\Models\User;
use App\Notifications\Module4PreventionMailNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class Module4NotificationService
{
    public function forUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $user->module4Notifications()
            ->with('equipement')
            ->when($filters['canal'] ?? null, fn ($query, $canal) => $query->where('canal', $canal))
            ->when(isset($filters['lue']) && $filters['lue'] !== '', fn ($query) => $query->where('lue', (bool) $filters['lue']))
            ->latest('date_envoi')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function generateForUser(User $user): int
    {
        $created = 0;
        $user->loadMissing('equipementsSensibles');

        foreach ($user->equipementsSensibles->where('actif', true) as $equipement) {
            $key = sprintf('equipment:%d:prevention:%s', $equipement->id, now()->format('Y-m-d'));
            if (Module4Notification::where('deduplication_key', $key)->exists()) {
                continue;
            }

            $notification = Module4Notification::create([
                'user_id' => $user->id,
                'equipement_sensible_id' => $equipement->id,
                'message' => $this->messageFor($equipement),
                'canal' => 'email',
                'date_envoi' => now(),
                'lue' => false,
                'deduplication_key' => $key,
            ]);
            $notification->load('equipement');
            try {
                $user->notify(new Module4PreventionMailNotification($notification));
            } catch (TransportExceptionInterface $exception) {
                Log::warning('Envoi e-mail Module 4 impossible. La notification reste disponible dans l’application.', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $exception->getMessage(),
                ]);
            }
            $created++;
        }

        return $created;
    }

    public function messageFor(EquipementSensible $equipement): string
    {
        return match ($equipement->type) {
            'medicament' => $equipement->niveau_sensibilite === 'eleve'
                ? 'Protégez vos médicaments contre la chaleur et prévoyez une solution de conservation adaptée.'
                : 'Vérifiez régulièrement les conditions de conservation de vos médicaments.',
            'appareil_medical' => $equipement->niveau_sensibilite === 'eleve'
                ? 'Vérifiez l’alimentation de votre appareil médical et prévoyez une solution de secours.'
                : 'Gardez votre appareil médical accessible et vérifiez son alimentation.',
            'frigo' => 'En cas de coupure, évitez d’ouvrir fréquemment votre réfrigérateur afin de préserver les aliments.',
            'ventilateur' => 'Gardez votre ventilateur accessible et prévoyez une alternative en cas de coupure.',
            default => 'Vérifiez les précautions nécessaires pour protéger cet équipement pendant une forte chaleur.',
        };
    }

    public function markAsRead(User $user, Module4Notification $notification): void
    {
        abort_unless($notification->user_id === $user->id, 404);
        $notification->update(['lue' => true]);
    }
}

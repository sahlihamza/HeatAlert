<?php

namespace App\Services;

use App\Models\EquipementSensible;
use App\Models\Module4Notification;
use App\Models\User;
use App\Notifications\EquipementSensibleZoneMailNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class EquipementSensibleService
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EquipementSensible::with(['zone', 'user']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('nom', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('adresse', 'like', "%{$search}%")
                    ->orWhereHas('zone', fn ($zone) => $zone->where('nom', 'like', "%{$search}%")->orWhere('ville', 'like', "%{$search}%"));
            });
        }

        foreach (['zone_id', 'type', 'niveau_sensibilite'] as $filter) {
            if (!empty($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (isset($filters['actif']) && $filters['actif'] !== '') {
            $query->where('actif', (bool) (int) $filters['actif']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function getForUserPaginated(User $user, array $filters = [], int $perPage = 8): LengthAwarePaginator
    {
        $query = EquipementSensible::with(['zone', 'latestRiskPrediction'])->where('user_id', $user->id);
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($q) => $q->where('nom', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }
        if (!empty($filters['niveau_sensibilite'])) {
            $query->where('niveau_sensibilite', $filters['niveau_sensibilite']);
        }
        if (isset($filters['actif']) && $filters['actif'] !== '') {
            $query->where('actif', (bool) $filters['actif']);
        }
        return $query->orderBy('nom')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): EquipementSensible
    {
        return EquipementSensible::create($data);
    }

    public function update(EquipementSensible $equipement, array $data): bool
    {
        return $equipement->update($data);
    }

    public function delete(EquipementSensible $equipement): bool
    {
        return $equipement->delete();
    }

    /**
     * Envoie une notification par e-mail et enregistre l'historique pour les utilisateurs de la même zone.
     */
    public function notifyUsersInSameZone(EquipementSensible $equipement, string $action = 'created'): int
    {
        if (!$equipement->zone_id) {
            return 0;
        }

        $equipement->loadMissing('zone');

        $users = User::where('zone_id', $equipement->zone_id)
            ->whereNotNull('email')
            ->get();

        $sentCount = 0;
        foreach ($users as $user) {
            // Création de l'enregistrement in-app Module 4
            try {
                Module4Notification::create([
                    'user_id' => $user->id,
                    'equipement_sensible_id' => $equipement->id,
                    'message' => ($action === 'created' ? 'Nouvel équipement sensible référencé dans votre zone : ' : 'Mise à jour d’un équipement sensible dans votre zone : ') . $equipement->nom,
                    'canal' => 'email',
                    'date_envoi' => now(),
                    'lue' => false,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Impossible d'enregistrer la notification Module 4 : " . $e->getMessage());
            }

            // Envoi de l'e-mail
            try {
                $user->notify(new EquipementSensibleZoneMailNotification($equipement, $action));
                $sentCount++;
            } catch (\Throwable $e) {
                Log::warning("Impossible d'envoyer l'e-mail équipement sensible à l'utilisateur {$user->id} ({$user->email}) : " . $e->getMessage());
            }
        }

        return $sentCount;
    }
}

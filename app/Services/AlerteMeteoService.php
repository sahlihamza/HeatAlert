<?php

namespace App\Services;

use App\Models\AlerteMeteo;
use App\Models\User;
use App\Notifications\HighLevelWeatherAlertNotification;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service pour la gestion des Alertes Météo.
 */
class AlerteMeteoService
{
    /**
     * Retourne les alertes paginées avec la zone associée.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return AlerteMeteo::with('zone')
            ->orderByDesc('date_debut')
            ->paginate($perPage);
    }

    /**
     * Crée une nouvelle alerte météo.
     */
    public function create(array $data): AlerteMeteo
    {
        $alerte = AlerteMeteo::create($data);
        $this->notifyIfHighLevel($alerte);
        return $alerte;
    }

    /**
     * Met à jour une alerte météo.
     */
    public function update(AlerteMeteo $alerte, array $data): bool
    {
        $updated = $alerte->update($data);
        if ($updated) {
            $this->notifyIfHighLevel($alerte);
        }
        return $updated;
    }

    /**
     * Supprime une alerte météo.
     */
    public function delete(AlerteMeteo $alerte): bool
    {
        return $alerte->delete();
    }

    private function notifyIfHighLevel(AlerteMeteo $alerte): void
    {
        if ($alerte->statut === 'active' && in_array($alerte->niveau, ['orange', 'rouge'], true)) {
            User::where('role', User::ROLE_USER)->get()
                ->each(fn (User $user) => $user->notify(new HighLevelWeatherAlertNotification($alerte)));
        }
    }
}

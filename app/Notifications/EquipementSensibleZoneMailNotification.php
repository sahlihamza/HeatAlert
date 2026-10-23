<?php

namespace App\Notifications;

use App\Models\EquipementSensible;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EquipementSensibleZoneMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param EquipementSensible $equipement
     * @param string $action 'created' | 'updated'
     */
    public function __construct(
        public EquipementSensible $equipement,
        public string $action = 'created'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $zoneName = $this->equipement->zone?->nom ?? 'votre zone';
        $zoneVille = $this->equipement->zone?->ville ? " ({$this->equipement->zone->ville})" : '';
        $isCreation = $this->action === 'created';

        $subject = $isCreation
            ? "HeatAlert — Nouvel équipement sensible dans votre zone : {$zoneName}"
            : "HeatAlert — Mise à jour d'un équipement sensible dans votre zone : {$zoneName}";

        $greeting = 'Bonjour ' . ($notifiable->prenom ?? $notifiable->name ?? 'citoyen');

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting);

        if ($isCreation) {
            $mail->line("Un nouvel équipement sensible vient d'être référencé dans votre secteur géographique ({$zoneName}{$zoneVille}).");
        } else {
            $mail->line("Les informations d'un équipement sensible dans votre secteur géographique ({$zoneName}{$zoneVille}) ont été mises à jour.");
        }

        $mail->line('• **Nom de l’équipement :** ' . $this->equipement->nom)
             ->line('• **Type :** ' . $this->equipement->type_label)
             ->line('• **Niveau de sensibilité :** ' . $this->equipement->niveau_label);

        if ($this->equipement->adresse) {
            $mail->line('• **Emplacement / Adresse :** ' . $this->equipement->adresse);
        }

        if ($this->equipement->description) {
            $mail->line('• **Consignes & Description :** ' . $this->equipement->description);
        }

        $mail->line('En période de forte chaleur ou de coupure d’électricité, restez attentif aux alertes et suivez les consignes de précaution pour protéger votre santé et vos proches.')
             ->action('Consulter la plateforme HeatAlert', url('/'))
             ->salutation('L’équipe HeatAlert — Sécurité & Vigilance');

        return $mail;
    }
}

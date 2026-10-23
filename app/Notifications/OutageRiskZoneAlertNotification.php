<?php

namespace App\Notifications;

use App\Models\PredictionCoupure;
use App\Models\Zone;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OutageRiskZoneAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Zone $zone,
        public PredictionCoupure $prediction
    ) {}

    public function via(object $notifiable): array
    {
        // Envoi par base de données (système in-app / push) et par email
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $probPct = round($this->prediction->probability * 100, 1);
        $timeWindow = '';
        if ($this->prediction->estimated_outage_start && $this->prediction->estimated_outage_end) {
            $timeWindow = ' Créneau estimé : ' . $this->prediction->estimated_outage_start->format('d/m à H:i') . ' → ' . $this->prediction->estimated_outage_end->format('H:i') . '.';
        }

        return new DatabaseMessage([
            'title' => '⚡ Alerte Préventive Coupure : ' . $this->zone->nom,
            'message' => "Risque élevé de coupure ({$probPct}%) détecté pour votre zone suite aux fortes chaleurs.{$timeWindow}",
            'risk_level' => $this->prediction->risk_level,
            'probability' => $this->prediction->probability,
            'zone_id' => $this->zone->id,
            'zone_nom' => $this->zone->nom,
            'url' => route('front.coupures.index'),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $probPct = round($this->prediction->probability * 100, 1);
        $dateText = '';
        if ($this->prediction->estimated_outage_start && $this->prediction->estimated_outage_end) {
            $dateText = 'entre ' . $this->prediction->estimated_outage_start->format('d/m/Y à H:i') . ' et ' . $this->prediction->estimated_outage_end->format('H:i');
        } else {
            $dateText = 'dans les prochaines 24 heures';
        }

        return (new MailMessage)
            ->subject('⚡ HeatAlert — Alerte préventive de coupure électrique : ' . $this->zone->nom)
            ->greeting('Bonjour ' . ($notifiable->prenom ?? $notifiable->nom ?? 'Citoyen') . ',')
            ->line("Le système prédictif HeatAlert a détecté un risque critique de coupure électrique de {$probPct}% dans votre zone ({$this->zone->nom}).")
            ->line("Créneau prévisionnel : {$dateText}.")
            ->line('Nos recommandations pour anticiper cette coupure :')
            ->line('• Rechargez vos téléphones portables et batteries de secours.')
            ->line('• Maintenez vos réfrigérateurs et congélateurs fermés pour conserver le froid.')
            ->line('• Prévoyez des réserves d’eau potable et lampes autonomes.')
            ->action('Consulter les détails des coupures', route('front.coupures.index'))
            ->salutation('L’équipe de vigilance HeatAlert');
    }
}

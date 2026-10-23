<?php

namespace App\Notifications;

use App\Models\Module4Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Module4PreventionMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Module4Notification $notification) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('HeatAlert — Conseil pour votre préparation personnelle')
            ->greeting('Bonjour ' . ($notifiable->prenom ?? $notifiable->name ?? ''))
            ->line($this->notification->message)
            ->line('Ce conseil concerne : ' . ($this->notification->equipement?->nom ?? 'votre préparation personnelle'))
            ->action('Consulter mes notifications', route('front.module4_notifications.index'))
            ->salutation('L’équipe HeatAlert');
    }
}

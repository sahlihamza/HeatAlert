<?php

namespace App\Notifications;

use App\Models\AlerteMeteo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class HighLevelWeatherAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AlerteMeteo $alerte)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Alerte météo de niveau '.$this->alerte->niveau,
            'message' => $this->alerte->titre,
            'niveau' => $this->alerte->niveau,
            'alerte_id' => $this->alerte->id,
            'url' => route('front.alertes.show', $this->alerte),
        ]);
    }
}

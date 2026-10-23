<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $prenom;

    public function __construct(User $user)
    {
        $this->code   = $user->two_factor_code;
        $this->prenom = $user->prenom;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🛡️ Code de connexion HeatAlert (2FA) — ' . $this->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.two-factor-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

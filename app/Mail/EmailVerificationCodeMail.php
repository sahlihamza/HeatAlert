<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $prenom;

    public function __construct(string|User $target, ?string $prenom = null)
    {
        if ($target instanceof User) {
            $this->code   = (string) $target->email_verification_code;
            $this->prenom = (string) $target->prenom;
        } else {
            $this->code   = (string) $target;
            $this->prenom = (string) ($prenom ?? 'Utilisateur');
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔐 Vérification de votre compte HeatAlert — Code : ' . $this->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

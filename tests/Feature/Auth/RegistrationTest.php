<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerificationCodeMail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // L'inscription envoie un email de vérification — on fake les mails
        Mail::fake();

        $response = $this->post('/register', [
            'nom'                  => 'Dupont',
            'prenom'               => 'Jean',
            'email'                => 'jean.dupont@example.com',
            'password'             => 'password',
            'password_confirmation' => 'password',
        ]);

        // L'utilisateur N'est PAS encore authentifié (vérification email en attente)
        $this->assertGuest();

        // L'app doit avoir envoyé exactement 1 email de vérification
        Mail::assertSent(EmailVerificationCodeMail::class, 1);

        // Redirige vers la page de saisie du code de vérification
        $response->assertRedirect(route('verification.code.show'));
    }
}

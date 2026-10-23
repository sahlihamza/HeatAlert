<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Models\Zone;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Affiche le formulaire d'inscription.
     */
    public function create(): View
    {
        $zones = Zone::where('actif', true)->orderBy('gouvernorat')->orderBy('ville')->get();
        return view('auth.register', compact('zones'));
    }

    /**
     * Traite la pré-inscription :
     * 1. Valide les données
     * 2. Stocke temporairement les informations en session (pas encore en BDD)
     * 3. Génère et envoie le code de vérification par email
     * 4. Redirige vers la page de saisie du code
     * L'utilisateur n'est créé en base de données qu'après validation du code !
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nom'       => ['required', 'string', 'max:60'],
            'prenom'    => ['required', 'string', 'max:60'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'regex:/^[+\d][\d\s\-]{7,19}$/'],
            'zone_id'   => ['nullable', 'exists:zones,id'],
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Générer le code à 6 chiffres
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Stocker en session en attente de vérification (15 minutes de validité)
        $request->session()->put('pending_registration', [
            'data'       => $request->only(['nom', 'prenom', 'email', 'telephone', 'zone_id', 'password']),
            'code'       => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Envoyer le code à l'adresse email
        Mail::to($request->email)->send(new EmailVerificationCodeMail($code, $request->prenom));

        return redirect()->route('verification.code.show');
    }
}

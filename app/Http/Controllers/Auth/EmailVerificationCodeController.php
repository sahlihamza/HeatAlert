<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Gère la vérification email par code à 6 chiffres.
 * L'utilisateur ne s'enregistre dans la base de données
 * que LORSQUE son code email est validé avec succès.
 */
class EmailVerificationCodeController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Affiche la page de saisie du code.
     */
    public function show(Request $request): View|RedirectResponse
    {
        // Si l'utilisateur est déjà connecté avec email vérifié
        if (Auth::check() && $request->user()->email_verified_at) {
            return redirect()->intended(route('front.home'));
        }

        // Si aucune inscription en attente et aucun utilisateur connecté
        if (! $request->session()->has('pending_registration') && ! Auth::check()) {
            return redirect()->route('register')
                ->with('error', 'Aucune inscription en cours. Veuillez remplir le formulaire d\'inscription.');
        }

        $email = $request->session()->get('pending_registration.data.email')
            ?? ($request->user() ? $request->user()->email : '');

        return view('auth.verify-email-code', compact('email'));
    }

    /**
     * Vérifie le code saisi par l'utilisateur.
     * C'est ICI que l'utilisateur est créé en base de données.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Veuillez saisir le code de vérification.',
            'code.size'     => 'Le code doit contenir exactement 6 chiffres.',
        ]);

        // Cas 1 : Pré-inscription en attente en session (non encore dans la BDD)
        if ($request->session()->has('pending_registration')) {
            $pending   = $request->session()->get('pending_registration');
            $code      = $pending['code'] ?? null;
            $expiresAt = isset($pending['expires_at']) ? Carbon::parse($pending['expires_at']) : null;
            $data      = $pending['data'] ?? [];

            // Vérifier le code et son expiration
            if (! $code || $request->code !== $code || ! $expiresAt || $expiresAt->isPast()) {
                return back()->withErrors([
                    'code' => 'Code invalide ou expiré. Veuillez en demander un nouveau.',
                ]);
            }

            // Vérifier si l'email a été pris entre-temps
            if (User::where('email', $data['email'])->exists()) {
                $request->session()->forget('pending_registration');
                return redirect()->route('register')->withErrors([
                    'email' => 'Un compte existe déjà avec cette adresse email.',
                ]);
            }

            // ── CRÉATION RÉELLE EN BASE DE DONNÉES (Seulement après validation) ──
            $user = $this->authService->createUser(array_merge($data, [
                'email_verified_at' => now(),
            ]));

            // Nettoyer la session d'attente
            $request->session()->forget('pending_registration');

            // Connecter le nouvel utilisateur
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('front.home')->with('status', 'email-verified');
        }

        // Cas 2 : Utilisateur existant déjà connecté mais non vérifié (fallback)
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            if (! $user->isEmailVerificationCodeValid($request->code)) {
                return back()->withErrors([
                    'code' => 'Code invalide ou expiré. Veuillez en demander un nouveau.',
                ]);
            }

            $user->update([
                'email_verified_at'             => now(),
                'email_verification_code'       => null,
                'email_verification_expires_at' => null,
            ]);

            return redirect()->route('front.home')->with('status', 'email-verified');
        }

        return redirect()->route('register');
    }

    /**
     * Renvoie un nouveau code par email.
     */
    public function resend(Request $request): RedirectResponse
    {
        // Cas 1 : Inscription en session
        if ($request->session()->has('pending_registration')) {
            $pending = $request->session()->get('pending_registration');
            $data    = $pending['data'] ?? [];

            $newCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $request->session()->put('pending_registration.code', $newCode);
            $request->session()->put('pending_registration.expires_at', now()->addMinutes(15));

            Mail::to($data['email'])->send(new EmailVerificationCodeMail($newCode, $data['prenom'] ?? ''));

            return back()->with('status', 'code-resent');
        }

        // Cas 2 : Utilisateur en BDD
        /** @var User|null $user */
        $user = $request->user();
        if ($user) {
            $user->generateEmailVerificationCode();
            Mail::to($user->email)->send(new EmailVerificationCodeMail($user));
            return back()->with('status', 'code-resent');
        }

        return redirect()->route('register');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * Gère tout ce qui concerne la 2FA TOTP (QR Code).
 *  - Afficher le QR Code pour configuration
 *  - Activer / Désactiver la 2FA
 *  - Vérifier le code TOTP au login
 */
class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ─────────────────────────────────────────────────────────────
    // CONFIGURATION (profil)
    // ─────────────────────────────────────────────────────────────

    /**
     * Génère un nouveau secret + QR Code SVG pour l'activation.
     * Retourne les données JSON pour affichage inline dans la page profil.
     */
    public function setup(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        // Générer un nouveau secret temporaire (pas encore activé)
        $secret = $this->google2fa->generateSecretKey();
        session(['2fa_setup_secret' => $secret]);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'HeatAlert'),
            $user->email,
            $secret
        );

        // Générer le SVG du QR Code
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(220),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $qrSvg  = $writer->writeString($qrCodeUrl);

        return response()->json([
            'qr_svg' => $qrSvg,
            'secret' => $secret,
        ]);
    }

    /**
     * Active la 2FA après confirmation du code TOTP.
     */
    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'totp_code' => ['required', 'string', 'size:6'],
        ], [
            'totp_code.required' => 'Veuillez saisir le code de votre application d\'authentification.',
            'totp_code.size'     => 'Le code doit contenir 6 chiffres.',
        ]);

        /** @var User $user */
        $user   = $request->user();
        $secret = session('2fa_setup_secret');

        if (! $secret) {
            return redirect()->route('profile.edit')
                ->with('2fa_error', 'Session expirée. Veuillez recommencer la configuration.');
        }

        $valid = $this->google2fa->verifyKey($secret, $request->totp_code);

        if (! $valid) {
            return redirect()->route('profile.edit')
                ->withErrors(['totp_code' => 'Code TOTP invalide. Vérifiez l\'heure de votre téléphone et réessayez.'])
                ->with('open_2fa_setup', true);
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret'  => encrypt($secret),
        ]);

        session()->forget('2fa_setup_secret');

        return redirect()->route('profile.edit')
            ->with('status', '2fa-enabled');
    }

    /**
     * Désactive la 2FA après confirmation du mot de passe.
     */
    public function disable(Request $request): RedirectResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'password' => ['required', 'current_password'],
        ], [
            'password.required'         => 'Veuillez saisir votre mot de passe.',
            'password.current_password' => 'Le mot de passe est incorrect.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('profile.edit')
                ->withErrors($validator, 'disable2fa');
        }

        /** @var User $user */
        $user = $request->user();

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret'  => null,
        ]);

        return redirect()->route('profile.edit')
            ->with('status', '2fa-disabled');
    }

    // ─────────────────────────────────────────────────────────────
    // VÉRIFICATION AU LOGIN
    // ─────────────────────────────────────────────────────────────

    /**
     * Affiche le formulaire de saisie du code TOTP au login.
     */
    public function challenge(Request $request): View|RedirectResponse
    {
        if (! session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Vérifie le code TOTP saisi au login.
     */
    public function verify(Request $request): RedirectResponse
    {
        if (! session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ], [
            'code.required' => 'Veuillez saisir votre code TOTP.',
        ]);

        /** @var User|null $user */
        $user = User::find(session('2fa_user_id'));

        if (! $user || ! $user->two_factor_enabled || ! $user->two_factor_secret) {
            session()->forget('2fa_user_id');
            return redirect()->route('login');
        }

        $secret = decrypt($user->two_factor_secret);
        $valid  = $this->google2fa->verifyKey($secret, $request->code);

        if (! $valid) {
            return back()->withErrors([
                'code' => 'Code invalide. Vérifiez votre application d\'authentification.',
            ]);
        }

        // Authentification réussie
        session()->forget('2fa_user_id');
        Auth::login($user, session()->pull('2fa_remember', false));
        $request->session()->regenerate();

        return redirect()->intended(
            $user->isAdmin() ? route('admin.dashboard') : route('front.home')
        );
    }
}

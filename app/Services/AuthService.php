<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Service d'authentification.
 * Centralise la logique métier liée à l'auth (redirection par rôle, création user...).
 */
class AuthService
{
    /**
     * Retourne la route de redirection selon le rôle de l'utilisateur.
     */
    public function getRedirectRouteForUser(User $user): string
    {
        return $user->isAdmin()
            ? route('admin.dashboard')
            : route('front.home');
    }

    /**
     * Crée un nouveau utilisateur avec le rôle ROLE_USER par défaut.
     */
    public function createUser(array $data): User
    {
        return User::create([
            'nom'              => $data['nom'],
            'prenom'           => $data['prenom'],
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'telephone'        => $data['telephone'] ?? null,
            'zone_id'          => $data['zone_id'] ?? null,
            'role'              => User::ROLE_USER,
            'date_inscription'  => now(),
            'email_verified_at' => $data['email_verified_at'] ?? null,
        ]);
    }
}

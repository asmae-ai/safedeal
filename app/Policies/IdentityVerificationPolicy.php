<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class IdentityVerificationPolicy
{
    /**
     * Seuls les vendeurs peuvent soumettre une vérification.
     * Un acheteur n'a pas besoin de vérifier son identité.
     */
    public function submit(User $user): bool
    {
        return $user->role === UserRole::VENDOR;
    }

    /**
     * Seuls les admins peuvent voir les vérifications en attente.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Seuls les admins peuvent approuver ou rejeter.
     */
    public function review(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}

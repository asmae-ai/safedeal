<?php

namespace App\Enums;

enum UserRole: string
{
    case VENDOR = 'vendor';
    case BUYER  = 'buyer';
    case ADMIN  = 'admin';

    public function label(): string
    {
        return match($this) {
            UserRole::VENDOR => 'Vendeur',
            UserRole::BUYER  => 'Acheteur',
            UserRole::ADMIN  => 'Administrateur',
        };
    }

    public function isVendor(): bool { return $this === UserRole::VENDOR; }
    public function isBuyer(): bool  { return $this === UserRole::BUYER; }
    public function isAdmin(): bool  { return $this === UserRole::ADMIN; }
}
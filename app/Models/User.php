<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'identity_status',
        'reputation_score',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'reputation_score'  => 'decimal:2',
        ];
    }

    public function isVendor(): bool { return $this->role === UserRole::VENDOR; }
    public function isBuyer(): bool  { return $this->role === UserRole::BUYER; }
    public function isAdmin(): bool  { return $this->role === UserRole::ADMIN; }

    public function isIdentityVerified(): bool
    {
        return $this->identity_status === 'approved';
    }
}
<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        return [
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'phone'               => $user->phone,
            'role'                => $role,
            'is_verified'         => $user->isIdentityVerified(),
            'verification_status' => $user->identity_status ?? 'not_submitted',
            'reputation_score'    => $user->reputation_score ?? 0,
            'created_at'          => $user->created_at?->toISOString(),
        ];
    }
}
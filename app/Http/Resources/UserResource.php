<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'role'                => $this->role->value,
            'is_verified'         => $this->isIdentityVerified(),
            'verification_status' => $this->identity_status ?? 'not_submitted',
            'reputation_score'    => $this->reputation_score ?? 0,
            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
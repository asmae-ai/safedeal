<?php

namespace App\Http\Resources;

use App\Models\IdentityVerification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IdentityVerification */
class IdentityVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var IdentityVerification $verification */
        $verification = $this->resource;

        return [
            'id' => $verification->id,
            'status' => $verification->status,
            'id_document_type' => $verification->id_document_type,
            'submitted_at' => $verification->created_at?->toISOString(),
            'reviewed_at' => $verification->reviewed_at?->toISOString(),
            'rejection_reason' => $verification->rejection_reason,
        ];
    }
}

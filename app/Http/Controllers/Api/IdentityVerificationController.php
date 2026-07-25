<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\IdentityVerification\SubmitVerificationRequest;
use App\Services\IdentityVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdentityVerificationController extends Controller
{
    public function __construct(
        private IdentityVerificationService $verificationService
    ) {}

    public function submit(SubmitVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        // Sécurité : seuls les vendeurs peuvent soumettre
        if ($user->role !== UserRole::VENDOR) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($this->verificationService->hasPendingVerification($user)) {
            return response()->json([
                'message' => 'A verification request is already pending',
            ], 409);
        }

        $verification = $this->verificationService->submit($request, $user);

        return response()->json([
            'message' => 'Verification request submitted successfully',
            'verification_status' => $verification->status,
        ], 201);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $verification = $this->verificationService->getStatus($user);

        if (! $verification) {
            return response()->json([
                'verification_status' => 'not_submitted',
                'submitted_at' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]);
        }

        return response()->json([
            'verification_status' => $verification->status,
            'submitted_at' => $verification->created_at?->toISOString(),
            'reviewed_at' => $verification->reviewed_at?->toISOString(),
            'rejection_reason' => $verification->rejection_reason,
        ]);
    }
}

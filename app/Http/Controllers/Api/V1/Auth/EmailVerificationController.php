<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Services\EmailVerificationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyEmailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $service,
    ) {}

    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($this->service->isVerified($user)) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $verified = $this->service->verify($user, $request->validated('code'));

        if (! $verified) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        }

        return response()->json(['message' => 'Email verified successfully.']);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($this->service->isVerified($user)) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        try {
            $this->service->send($user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        }

        return response()->json(['message' => 'Verification code sent.']);
    }
}
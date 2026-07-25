<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Exceptions\InvalidOtpException;
use App\Domain\Auth\Exceptions\OtpBlockedException;
use App\Domain\Auth\Exceptions\OtpCooldownException;
use App\Domain\Auth\Exceptions\OtpExpiredException;
use App\Domain\Auth\Services\TwoFactorService;
use App\Domain\Shared\Contracts\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyTwoFactorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        $ip = $request->ip() ?? '0.0.0.0';

        try {
            $this->twoFactorService->send($user, $ip);
        } catch (OtpCooldownException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        }

        return response()->json(['message' => 'OTP sent to your email address.']);
    }

    public function verify(VerifyTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();
        $ip = $request->ip() ?? '0.0.0.0';

        try {
            $this->twoFactorService->verify($user, $request->validated('code'), $ip);

            return response()->json([
                'message' => '2FA verification successful.',
                'verified_at' => now()->toIso8601String(),
            ]);

        } catch (OtpExpiredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (OtpBlockedException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        } catch (InvalidOtpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}

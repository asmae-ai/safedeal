<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyTwoFactorRequest;
use App\Domain\Auth\Exceptions\OtpExpiredException;
use App\Domain\Auth\Exceptions\OtpInvalidException;
use App\Domain\Auth\Exceptions\OtpMaxAttemptsExceededException;
use App\Domain\Auth\Services\TwoFactorService;
use App\Domain\Shared\Contracts\AuditLogger;
use App\Domain\Shared\ValueObjects\SecurityEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly AuditLogger      $auditLogger,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->twoFactorService->sendOtp($user);

        $this->auditLogger->log(new SecurityEvent(
            type:    '2fa_otp_sent',
            payload: ['user_id' => $user->id, 'email' => $user->email],
        ));

        return response()->json(['message' => 'OTP sent to your email address.']);
    }

    public function verify(VerifyTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $this->twoFactorService->verifyOtp($user, $request->validated('code'));

            $this->auditLogger->log(new SecurityEvent(
                type:    '2fa_verified',
                payload: ['user_id' => $user->id],
            ));

            return response()->json([
                'message'     => '2FA verification successful.',
                'verified_at' => now()->toIso8601String(),
            ]);

        } catch (OtpExpiredException) {
            return response()->json(['message' => 'OTP has expired.'], 422);
        } catch (OtpMaxAttemptsExceededException) {
            return response()->json(['message' => 'Too many attempts.'], 429);
        } catch (OtpInvalidException) {
            return response()->json(['message' => 'Invalid OTP code.'], 422);
        }
    }
}
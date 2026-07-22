<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Domain\Auth\ValueObjects\PasswordResetToken;
use App\Domain\Shared\Contracts\AuditLogger;
use App\Domain\Shared\ValueObjects\SecurityEvent;
use App\Infrastructure\Auth\DatabasePasswordResetTokenRepository;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

final class PasswordResetController extends Controller
{
    public function __construct(
        private readonly DatabasePasswordResetTokenRepository $tokenRepository,
        private readonly AuditLogger                          $auditLogger,
    ) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if ($user) {
            $token = PasswordResetToken::create($user->email, ttlMinutes: 60);
            $this->tokenRepository->store($token);
            $user->notify(new PasswordResetNotification($token->getValue()));

            $this->auditLogger->record(SecurityEvent::info(
                'password_reset_requested',
                ['user_id' => $user->id],
            ));
        }

        return response()->json([
            'message' => 'If this email exists, a reset link has been sent.',
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! $this->tokenRepository->isValid($data['email'], $data['token'])) {
            $this->auditLogger->record(SecurityEvent::warn(
                'password_reset_token_invalid',
                ['email' => $data['email']],
            ));

            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->update(['password' => Hash::make($data['password'])]);
        $user->tokens()->each(fn ($token) => $token->revoke());

        $this->tokenRepository->delete($data['email']);

        $this->auditLogger->record(SecurityEvent::info(
            'password_reset_success',
            ['user_id' => $user->id],
        ));

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
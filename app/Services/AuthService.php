<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Auth\Services\EmailVerificationService;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {}

    public function register(RegisterRequest $request): array
    {
        $user  = User::create($request->validated());
        $token = $user->createToken('auth_token')->accessToken;

        $this->emailVerificationService->send($user);

        return compact('user', 'token');
    }

    public function login(LoginRequest $request): array
    {
        // 1. Vérifier les credentials
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw new AuthenticationException('Invalid credentials');
        }

        /** @var User $user */
        $user = Auth::user();

        // 2. Email non vérifié → 403 propre (pas un 500)
        if (! $user->hasVerifiedEmail()) {
            Auth::logout();
            throw new HttpResponseException(
                response()->json([
                    'message'        => 'Your email address is not verified.',
                    'email_verified' => false,
                    'resend_url'     => '/api/v1/auth/email/resend',
                ], 403)
            );
        }

        // 3. Révoquer tous les anciens tokens (sécurité)
        $user->tokens()->each(fn ($token) => $token->revoke());

        // 4. Générer un nouveau token
        $token = $user->createToken('auth_token')->accessToken;

        return compact('user', 'token');
    }

    public function logout(User $user): void
    {
        // Révoquer tous les tokens actifs
        $user->tokens()->each(fn ($token) => $token->revoke());
    }
}
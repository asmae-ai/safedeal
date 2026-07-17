<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\TransientToken;

class AuthService
{
    public function register(RegisterRequest $request): array
    {
        $user = User::create($request->validated());

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    public function login(LoginRequest $request): array
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw new AuthenticationException('Invalid credentials');
        }

        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    public function logout(User $user): void
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken instanceof TransientToken) {
            return;
        }

        $currentToken->delete();
    }
}
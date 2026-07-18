<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IdentityVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // Public routes — Rate limit strict (OWASP Brute Force Protection)
    Route::middleware('throttle:5,1')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('throttle:10,1')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Vérification d'identité
        Route::post('/verify-identity', [IdentityVerificationController::class, 'submit']);
        Route::get('/verify-identity/status', [IdentityVerificationController::class, 'status']);
    });

});
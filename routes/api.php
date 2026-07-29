<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IdentityVerificationController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

// Stripe Webhook — public, pas de auth
Route::post('/v1/stripe/webhook', [StripeWebhookController::class, 'handle']);

Route::prefix('v1')->group(function (): void {

    // Public routes
    Route::middleware('throttle:5,1')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('throttle:10,1')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
    });

    // Password Reset — public
    Route::prefix('auth/password')->group(function (): void {
        Route::post('/forgot', [PasswordResetController::class, 'forgot']);
        Route::post('/reset',  [PasswordResetController::class, 'reset']);
    });

    // Public — accessible sans auth via lien sécurisé
    Route::get('/transactions/{token}', [TransactionController::class, 'show']);

    // Protected routes
    Route::middleware('auth:api')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // Vérification d'identité
        Route::post('/verify-identity',        [IdentityVerificationController::class, 'submit']);
        Route::get('/verify-identity/status',  [IdentityVerificationController::class, 'status']);

        // Transactions
        Route::post('/transactions',                              [TransactionController::class, 'store']);
        Route::get('/transactions',                               [TransactionController::class, 'index']);
        Route::patch('/transactions/{transaction}/cancel',        [TransactionController::class, 'cancel']);
        Route::post('/transactions/{transaction}/pay',            [TransactionController::class, 'pay']);
        Route::post('/transactions/{transaction}/ship',           [TransactionController::class, 'ship']);
        Route::post('/transactions/{transaction}/deliver',        [TransactionController::class, 'deliver']);
        Route::post('/transactions/{transaction}/close',          [TransactionController::class, 'close']);
        Route::post('/transactions/{transaction}/checkout',       [TransactionController::class, 'checkout']);

        // 2FA
        Route::prefix('auth/2fa')->group(function (): void {
            Route::post('/send',   [TwoFactorController::class, 'send']);
            Route::post('/verify', [TwoFactorController::class, 'verify']);
        });

        // Email verification
        Route::prefix('auth/email')->group(function (): void {
            Route::post('/verify',  [EmailVerificationController::class, 'verify']);
            Route::post('/resend',  [EmailVerificationController::class, 'resend']);
        });
    });
});
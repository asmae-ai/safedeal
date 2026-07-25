<?php

namespace App\Providers;

use App\Domain\Auth\Contracts\EmailVerificationStore;
use App\Domain\Auth\Contracts\OtpGenerator;
use App\Domain\Auth\Contracts\OtpNotifier;
use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Policies\OtpConfiguration;
use App\Domain\Auth\Services\TwoFactorService;
use App\Domain\Shared\Contracts\AuditLogger;
use App\Infrastructure\Auth\DatabasePasswordResetTokenRepository;
use App\Infrastructure\Auth\EmailOtpNotifier;
use App\Infrastructure\Auth\MonologAuditLogger;
use App\Infrastructure\Auth\NumericOtpGenerator;
use App\Infrastructure\Auth\RedisEmailVerificationStore;
use App\Infrastructure\Auth\RedisOtpStore;
use App\Models\IdentityVerification;
use App\Models\Transaction;
use App\Policies\IdentityVerificationPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OtpConfiguration::class, fn () => new OtpConfiguration(
            ttl: (int) config('auth.otp.ttl', 600),
            cooldown: (int) config('auth.otp.cooldown', 120),
            maxAttempts: (int) config('auth.otp.max_attempts', 3),
            length: (int) config('auth.otp.length', 6),
            secret: (string) env('OTP_SECRET', config('app.key')),
        ));

        $this->app->bind(AuditLogger::class, MonologAuditLogger::class);
        $this->app->bind(OtpStore::class, RedisOtpStore::class);
        $this->app->bind(OtpNotifier::class, EmailOtpNotifier::class);
        $this->app->bind(OtpGenerator::class, NumericOtpGenerator::class);
        $this->app->singleton(TwoFactorService::class);
        $this->app->singleton(DatabasePasswordResetTokenRepository::class);
        $this->app->bind(EmailVerificationStore::class, RedisEmailVerificationStore::class);
    }

    public function boot(): void
    {
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(IdentityVerification::class, IdentityVerificationPolicy::class);
    }
}

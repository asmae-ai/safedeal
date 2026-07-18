<?php

namespace App\Providers;

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
        //
    }

    public function boot(): void
    {
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(IdentityVerification::class, IdentityVerificationPolicy::class);
    }
}
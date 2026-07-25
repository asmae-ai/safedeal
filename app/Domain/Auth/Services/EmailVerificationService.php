<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Contracts\EmailVerificationStore;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;

final class EmailVerificationService
{
    public function __construct(
        private readonly EmailVerificationStore $store,
    ) {}

    public function send(User $user): void
    {
        if ($this->store->hasRecentlySent($user->id())) {
            throw new \RuntimeException('Please wait before requesting a new code.');
        }

        $code = (string) random_int(100000, 999999);

        $this->store->store($user->id(), $code);
        $user->notify(new EmailVerificationNotification($code));
    }

    public function verify(User $user, string $code): bool
    {
        $stored = $this->store->retrieve($user->id());

        if ($stored === null || ! hash_equals($stored, $code)) {
            return false;
        }

        $user->update(['email_verified_at' => now()]);
        $this->store->delete($user->id());

        return true;
    }

    public function isVerified(User $user): bool
    {
        return $user->email_verified_at !== null;
    }
}

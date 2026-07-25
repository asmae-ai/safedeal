<?php

namespace App\Domain\Auth\ValueObjects;

final readonly class StoredOtp
{
    public function __construct(
        public readonly string $hashedValue,
        public readonly int $attempts,
    ) {}

    public function verify(OtpCode $otp, string $secret): bool
    {
        return hash_equals(
            $this->hashedValue,
            hash_hmac('sha256', $otp->value, $secret)
        );
    }

    public function isBlocked(int $maxAttempts): bool
    {
        return $this->attempts >= $maxAttempts;
    }
}

<?php

namespace App\Domain\Auth\Policies;

final readonly class OtpConfiguration
{
    public function __construct(
        private int $ttl,
        private int $cooldown,
        private int $maxAttempts,
        private int $length,
        private string $secret,
    ) {}

    public function ttl(): int
    {
        return $this->ttl;
    }

    public function cooldown(): int
    {
        return $this->cooldown;
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function length(): int
    {
        return $this->length;
    }

    public function secret(): string
    {
        return $this->secret;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Auth\ValueObjects;

use DateTimeImmutable;

final class PasswordResetToken
{
    private function __construct(
        private readonly string $value,
        private readonly string $email,
        private readonly DateTimeImmutable $expiresAt,
    ) {}

    public static function create(string $email, int $ttlMinutes = 60): self
    {
        return new self(
            value: bin2hex(random_bytes(32)),
            email: $email,
            expiresAt: new DateTimeImmutable("+{$ttlMinutes} minutes"),
        );
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable > $this->expiresAt;
    }
}

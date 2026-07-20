<?php

namespace App\Domain\Auth\ValueObjects;

final readonly class OtpCode
{
    public function __construct(
        public readonly string $value,
    ) {}
}
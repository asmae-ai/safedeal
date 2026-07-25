<?php

namespace App\Domain\Shared\ValueObjects;

final readonly class SecurityEvent
{
    public function __construct(
        public readonly string $name,
        public readonly string $level,
        public readonly array $context = [],
    ) {}

    public static function info(string $name, array $context = []): self
    {
        return new self($name, 'info', $context);
    }

    public static function warn(string $name, array $context = []): self
    {
        return new self($name, 'warning', $context);
    }

    public static function error(string $name, array $context = []): self
    {
        return new self($name, 'error', $context);
    }
}

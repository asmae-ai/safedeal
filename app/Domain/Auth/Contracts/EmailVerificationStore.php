<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

interface EmailVerificationStore
{
    public function store(int $userId, string $code): void;
    public function retrieve(int $userId): ?string;
    public function delete(int $userId): void;
    public function hasRecentlySent(int $userId): bool;
}
<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\ValueObjects\OtpCode;
use App\Domain\Auth\ValueObjects\StoredOtp;

interface OtpStore
{
    public function store(int $userId, OtpCode $otp): void;
    public function retrieve(int $userId): ?StoredOtp;
    public function delete(int $userId): void;
    public function hasRecentlySent(int $userId): bool;
    public function incrementAttempts(int $userId): void;
}
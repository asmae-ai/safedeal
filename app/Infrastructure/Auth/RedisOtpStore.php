<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Policies\OtpConfiguration;
use App\Domain\Auth\ValueObjects\OtpCode;
use App\Domain\Auth\ValueObjects\StoredOtp;
use Illuminate\Support\Facades\Redis;

final class RedisOtpStore implements OtpStore
{
    public function __construct(
        private readonly OtpConfiguration $config,
    ) {}

    public function store(int $userId, OtpCode $otp): void
    {
        $hashed = $this->hash($otp->value);

        Redis::pipeline(function ($pipe) use ($userId, $hashed) {
            $pipe->setex($this->key($userId, 'code'), $this->config->ttl(), $hashed);
            $pipe->setex($this->key($userId, 'cooldown'), $this->config->cooldown(), now()->timestamp);
            $pipe->setex($this->key($userId, 'attempts'), $this->config->ttl(), 0);
        });
    }

    public function retrieve(int $userId): ?StoredOtp
    {
        $hashed = Redis::get($this->key($userId, 'code'));
        $attempts = (int) Redis::get($this->key($userId, 'attempts'));

        if ($hashed === null) {
            return null;
        }

        return new StoredOtp(
            hashedValue: $hashed,
            attempts: $attempts,
        );
    }

    public function delete(int $userId): void
    {
        Redis::pipeline(function ($pipe) use ($userId) {
            $pipe->del($this->key($userId, 'code'));
            $pipe->del($this->key($userId, 'attempts'));
        });
    }

    public function hasRecentlySent(int $userId): bool
    {
        return (bool) Redis::exists($this->key($userId, 'cooldown'));
    }

    public function incrementAttempts(int $userId): void
    {
        Redis::incr($this->key($userId, 'attempts'));
    }

    private function key(int $userId, string $type): string
    {
        return "auth:otp:user:{$userId}:{$type}";
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, $this->config->secret());
    }
}

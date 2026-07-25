<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Contracts\EmailVerificationStore;
use Illuminate\Support\Facades\Redis;

final class RedisEmailVerificationStore implements EmailVerificationStore
{
    private const TTL = 600;  // 10 minutes

    private const COOLDOWN = 60;   // 1 minute

    public function store(int $userId, string $code): void
    {
        Redis::pipeline(function ($pipe) use ($userId, $code) {
            $pipe->setex($this->key($userId, 'code'), self::TTL, $code);
            $pipe->setex($this->key($userId, 'cooldown'), self::COOLDOWN, now()->timestamp);
        });
    }

    public function retrieve(int $userId): ?string
    {
        return Redis::get($this->key($userId, 'code'));
    }

    public function delete(int $userId): void
    {
        Redis::pipeline(function ($pipe) use ($userId) {
            $pipe->del($this->key($userId, 'code'));
            $pipe->del($this->key($userId, 'cooldown'));
        });
    }

    public function hasRecentlySent(int $userId): bool
    {
        return (bool) Redis::exists($this->key($userId, 'cooldown'));
    }

    private function key(int $userId, string $type): string
    {
        return "auth:email_verification:user:{$userId}:{$type}";
    }
}

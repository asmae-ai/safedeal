<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\ValueObjects\PasswordResetToken;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class DatabasePasswordResetTokenRepository
{
    private const TABLE = 'password_reset_tokens';

    public function store(PasswordResetToken $token): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            ['email' => $token->getEmail()],
            [
                'token' => Hash::make($token->getValue()),
                'created_at' => now(),
            ],
        );
    }

    public function isValid(string $email, string $plainToken, int $ttlMinutes = 60): bool
    {
        $record = DB::table(self::TABLE)->where('email', $email)->first();

        if (! $record) {
            return false;
        }

        $expiresAt = (new DateTimeImmutable($record->created_at))
            ->modify("+{$ttlMinutes} minutes");

        if (new DateTimeImmutable > $expiresAt) {
            return false;
        }

        return Hash::check($plainToken, $record->token);
    }

    public function delete(string $email): void
    {
        DB::table(self::TABLE)->where('email', $email)->delete();
    }
}

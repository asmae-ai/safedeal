<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Contracts\OtpGenerator;
use App\Domain\Auth\Contracts\OtpNotifier;
use App\Domain\Auth\Contracts\OtpRecipient;
use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Exceptions\InvalidOtpException;
use App\Domain\Auth\Exceptions\OtpBlockedException;
use App\Domain\Auth\Exceptions\OtpCooldownException;
use App\Domain\Auth\Exceptions\OtpExpiredException;
use App\Domain\Auth\Policies\OtpConfiguration;
use App\Domain\Shared\Contracts\AuditLogger;
use App\Domain\Shared\ValueObjects\SecurityEvent;

final class TwoFactorService
{
    public function __construct(
        private readonly OtpStore $store,
        private readonly OtpNotifier $notifier,
        private readonly OtpGenerator $generator,
        private readonly OtpConfiguration $config,
        private readonly AuditLogger $audit,
    ) {}

    public function send(OtpRecipient $recipient, string $ip): void
    {
        if ($this->store->hasRecentlySent($recipient->id())) {
            $this->audit->record(SecurityEvent::warn('otp.cooldown', [
                'user_id' => $recipient->id(),
                'ip' => $ip,
            ]));
            throw OtpCooldownException::make();
        }

        $otp = $this->generator->generate();

        $this->store->delete($recipient->id());
        $this->store->store($recipient->id(), $otp);
        $this->notifier->send($recipient, $otp);

        $this->audit->record(SecurityEvent::info('otp.sent', [
            'user_id' => $recipient->id(),
            'ip' => $ip,
        ]));
    }

    public function verify(OtpRecipient $recipient, string $rawOtp, string $ip): void
    {
        $stored = $this->store->retrieve($recipient->id());

        if ($stored === null) {
            throw OtpExpiredException::make();
        }

        if ($stored->isBlocked($this->config->maxAttempts())) {
            $this->audit->record(SecurityEvent::error('otp.blocked', [
                'user_id' => $recipient->id(),
                'ip' => $ip,
            ]));
            throw OtpBlockedException::make();
        }

        $otp = $this->generator->fromRaw($rawOtp);

        if (! $stored->verify($otp, $this->config->secret())) {
            $this->store->incrementAttempts($recipient->id());
            $this->audit->record(SecurityEvent::warn('otp.failed', [
                'user_id' => $recipient->id(),
                'ip' => $ip,
            ]));
            throw InvalidOtpException::make();
        }

        $this->store->delete($recipient->id());
        $this->audit->record(SecurityEvent::info('otp.verified', [
            'user_id' => $recipient->id(),
            'ip' => $ip,
        ]));
    }
}

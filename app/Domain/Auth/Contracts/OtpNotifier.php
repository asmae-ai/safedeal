<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\ValueObjects\OtpCode;

interface OtpNotifier
{
    public function send(OtpRecipient $recipient, OtpCode $otp): void;
}

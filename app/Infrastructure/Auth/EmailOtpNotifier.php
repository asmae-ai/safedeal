<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Contracts\OtpNotifier;
use App\Domain\Auth\Contracts\OtpRecipient;
use App\Domain\Auth\ValueObjects\OtpCode;
use App\Models\User;
use App\Notifications\OtpNotification;

final class EmailOtpNotifier implements OtpNotifier
{
    public function send(OtpRecipient $recipient, OtpCode $otp): void
    {
        $user = User::findOrFail($recipient->id());
        $user->notify(new OtpNotification($otp->value));
    }
}

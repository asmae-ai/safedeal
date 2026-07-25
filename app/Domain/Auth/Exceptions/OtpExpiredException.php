<?php

namespace App\Domain\Auth\Exceptions;

use DomainException;

final class OtpExpiredException extends DomainException
{
    public static function make(): self
    {
        return new self('Code OTP expiré.');
    }
}

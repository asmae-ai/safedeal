<?php

namespace App\Domain\Auth\Exceptions;

use DomainException;

final class InvalidOtpException extends DomainException
{
    public static function make(): self
    {
        return new self('Code OTP invalide.');
    }
}
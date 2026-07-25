<?php

namespace App\Domain\Auth\Exceptions;

use DomainException;

final class OtpBlockedException extends DomainException
{
    public static function make(): self
    {
        return new self('Trop de tentatives. Veuillez redemander un code.');
    }
}

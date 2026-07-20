<?php

namespace App\Domain\Auth\Exceptions;

use DomainException;

final class OtpCooldownException extends DomainException
{
    public static function make(): self
    {
        return new self('Veuillez attendre avant de renvoyer un code.');
    }
}
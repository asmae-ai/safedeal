<?php

namespace App\Domain\Auth\Contracts;

interface OtpRecipient
{
    public function id(): int;

    public function email(): string;
}

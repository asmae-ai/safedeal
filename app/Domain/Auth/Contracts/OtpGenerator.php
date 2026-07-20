<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\ValueObjects\OtpCode;

interface OtpGenerator
{
    public function generate(): OtpCode;
}
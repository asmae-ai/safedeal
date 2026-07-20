<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\Contracts\OtpGenerator;
use App\Domain\Auth\Policies\OtpConfiguration;
use App\Domain\Auth\ValueObjects\OtpCode;

final class NumericOtpGenerator implements OtpGenerator
{
    public function __construct(
        private readonly OtpConfiguration $config,
    ) {}

    public function generate(): OtpCode
    {
        $max = (int) str_repeat('9', $this->config->length());

        return new OtpCode(
            str_pad(
                string:     (string) random_int(0, $max),
                length:     $this->config->length(),
                pad_string: '0',
                pad_type:   STR_PAD_LEFT,
            )
        );
    }

    public function fromRaw(string $raw): OtpCode
    {
        return new OtpCode($raw);
    }
}
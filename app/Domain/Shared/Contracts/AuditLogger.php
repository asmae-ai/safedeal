<?php

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\ValueObjects\SecurityEvent;

interface AuditLogger
{
    public function record(SecurityEvent $event): void;
}
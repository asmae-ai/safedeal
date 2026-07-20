<?php

namespace App\Infrastructure\Auth;

use App\Domain\Shared\Contracts\AuditLogger;
use App\Domain\Shared\ValueObjects\SecurityEvent;
use Illuminate\Support\Facades\Log;

final class MonologAuditLogger implements AuditLogger
{
    public function record(SecurityEvent $event): void
    {
        Log::channel('security')->{$event->level}($event->name, $event->context);
    }
}
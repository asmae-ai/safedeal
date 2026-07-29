<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Transaction;

interface PaymentGatewayInterface
{
    public function createCheckoutSession(
        Transaction $transaction,
        string $successUrl,
        string $cancelUrl
    ): array;
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Transaction;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService implements PaymentGatewayInterface
{
    private StripeClient $stripe;

    private const ALLOWED_CURRENCIES = ['mad', 'eur', 'usd'];

    public function __construct()
    {
        if (blank(config('services.stripe.secret'))) {
            throw new \RuntimeException('Stripe n\'est pas configuré.');
        }

        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Transaction $transaction, string $successUrl, string $cancelUrl): array
    {
        if ($transaction->amount <= 0) {
            throw new \InvalidArgumentException('Le montant est invalide.');
        }

        if (! in_array(strtolower($transaction->currency), self::ALLOWED_CURRENCIES, true)) {
            throw new \InvalidArgumentException('Devise non supportée : '.$transaction->currency);
        }

        try {
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($transaction->currency),
                        'product_data' => [
                            'name' => $transaction->title,
                            'description' => $transaction->description ?? 'Transaction SafeDeal',
                        ],
                        'unit_amount' => (int) ($transaction->amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'vendor_id' => $transaction->vendor_id,
                    'buyer_id' => $transaction->buyer_id,
                ],
            ], [
                'idempotency_key' => 'transaction_'.$transaction->id,
            ]);

            $transaction->update([
                'stripe_session_id' => $session->id,
            ]);

            return [
                'id' => $session->id,
                'url' => $session->url,
            ];

        } catch (ApiErrorException $e) {
            report($e);
            throw new \RuntimeException('Impossible de créer la session Stripe : '.$e->getMessage());
        }
    }
}
         
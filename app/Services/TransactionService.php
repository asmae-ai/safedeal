<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Stripe\Event;

class TransactionService
{
    public function __construct(
        private readonly StripeService $stripeService,
    ) {}

    public function create(User $vendor, array $data): Transaction
    {
        if (($data['amount'] ?? 0) <= 0) {
            throw new DomainException('Le montant doit être supérieur à zéro.');
        }

        return DB::transaction(function () use ($vendor, $data) {
            return Transaction::create([
                'vendor_id' => $vendor->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'MAD',
                'status' => TransactionStatus::PendingPayment,
            ]);
        });
    }

    public function claim(Transaction $transaction, User $buyer): Transaction
    {
        if ($transaction->buyer_id !== null) {
            throw new DomainException('Cette transaction possède déjà un acheteur.');
        }

        if ($transaction->vendor_id === $buyer->id) {
            throw new DomainException('Le vendeur ne peut pas être l\'acheteur.');
        }

        if ($transaction->status !== TransactionStatus::PendingPayment) {
            throw new DomainException('Cette transaction ne peut plus être réclamée.');
        }

        $transaction->forceFill([
            'buyer_id' => $buyer->id,
        ])->save();

        return $transaction->fresh(['vendor', 'buyer']);
    }

    public function findByToken(string $token): Transaction
    {
        return Transaction::where('secure_token', $token)
            ->with(['vendor', 'buyer'])
            ->firstOrFail();
    }

    public function getPaginatedForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Transaction::with(['vendor', 'buyer'])
            ->where(function ($query) use ($user) {
                $query->where('vendor_id', $user->id)
                    ->orWhere('buyer_id', $user->id);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function createCheckoutSession(Transaction $transaction, string $successUrl, string $cancelUrl): array
    {
        return $this->stripeService->createCheckoutSession($transaction, $successUrl, $cancelUrl);
    }

    public function transitionTo(Transaction $transaction, TransactionStatus $newStatus): Transaction
    {
        if (! $transaction->canTransitionTo($newStatus)) {
            throw new DomainException(
                "Transition interdite : {$transaction->status->value} → {$newStatus->value}"
            );
        }

        return DB::transaction(function () use ($transaction, $newStatus) {
            $timestamps = match ($newStatus) {
                TransactionStatus::PaymentReceived => ['paid_at' => now()],
                TransactionStatus::InShipping => ['shipped_at' => now()],
                TransactionStatus::Delivered => ['delivered_at' => now()],
                TransactionStatus::Closed => ['closed_at' => now()],
                default => [],
            };

            $transaction->forceFill(array_merge(
                ['status' => $newStatus],
                $timestamps
            ))->save();

            return $transaction->fresh(['vendor', 'buyer']);
        });
    }

    public function cancel(Transaction $transaction): Transaction
    {
        return $this->transitionTo($transaction, TransactionStatus::Cancelled);
    }

    public function handleStripeWebhook(Event $event): void
    {
        if ($event->type !== 'checkout.session.completed') {
            return;
        }

        $session = $event->data->object;
        $transactionId = $session->metadata->transaction_id ?? null;

        if (! $transactionId) {
            return;
        }

        $transaction = Transaction::query()->find($transactionId);

        if (! $transaction) {
            return;
        }

        if ($transaction->status !== TransactionStatus::PendingPayment) {
            return;
        }

        $this->transitionTo(
            $transaction,
            TransactionStatus::PaymentReceived
        );
    }
}
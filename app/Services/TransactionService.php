<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function create(User $vendor, array $data): Transaction
    {
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

    public function transitionTo(Transaction $transaction, TransactionStatus $newStatus): Transaction
    {
        if (! $transaction->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Transition interdite : {$transaction->status->value} → {$newStatus->value}"
            );
        }

        return DB::transaction(function () use ($transaction, $newStatus) {
            $timestamps = match ($newStatus) {
                TransactionStatus::PaymentReceived => ['payment_at' => now()],
                TransactionStatus::InShipping => ['shipped_at' => now()],
                TransactionStatus::Delivered => ['delivered_at' => now()],
                TransactionStatus::Closed => ['closed_at' => now()],
                default => [],
            };

            $transaction->update(array_merge(
                ['status' => $newStatus],
                $timestamps
            ));

            return $transaction->fresh();
        });
    }

    public function cancel(Transaction $transaction): Transaction
    {
        return $this->transitionTo($transaction, TransactionStatus::Cancelled);
    }
}

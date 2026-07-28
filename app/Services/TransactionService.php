<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function create(User $vendor, array $data): Transaction
    {
        return DB::transaction(function () use ($vendor, $data) {
            $transaction = Transaction::create([
                'vendor_id' => $vendor->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'MAD',
                'secure_token' => Str::random(64),
                'status' => TransactionStatus::PendingPayment,
            ]);

            // TODO: Enregistrer le log d'audit pour la création

            return $transaction;
        });
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

            $transaction->update(array_merge(
                ['status' => $newStatus],
                $timestamps
            ));

            // TODO: Enregistrer le log d'audit pour le changement de statut

            return $transaction->fresh(['vendor', 'buyer']);
        });
    }

    public function cancel(Transaction $transaction): Transaction
    {
        $updated = $this->transitionTo($transaction, TransactionStatus::Cancelled);

        // TODO: Enregistrer le log d'audit pour l'annulation

        return $updated;
    }
}
<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function create(User $user): bool
    {
        return $user->role->value === 'vendor'
            && $user->identity_status === 'approved';
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->vendor_id
            || $user->id === $transaction->buyer_id
            || $user->role->value === 'admin';
    }

    public function cancel(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->vendor_id
            && $transaction->status->value === 'pending_payment';
    }

    public function listOwn(User $user): bool
    {
        return true;
    }
}

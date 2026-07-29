<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property TransactionStatus $status
 * @property float $amount
 * @property User|null $vendor
 * @property User|null $buyer
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'buyer_id',
        'title',
        'description',
        'amount',
        'currency',
        'tracking_number',
        'shipping_proof_path',
        'status',
    ];

    protected $casts = [
        'status'       => TransactionStatus::class,
        'amount'       => 'decimal:2',
        'paid_at'      => 'datetime',
        'shipped_at'   => 'datetime',
        'delivered_at' => 'datetime',
        'closed_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction): void {
            $transaction->secure_token ??= Str::uuid()->toString();
        });
    }

    // ─── Relations ───────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class, 'transaction_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::PendingPayment);
    }

    public function scopeForVendor(Builder $query, int $vendorId): Builder
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForBuyer(Builder $query, int $buyerId): Builder
    {
        return $query->where('buyer_id', $buyerId);
    }

    // ─── Accessors ───────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PendingPayment;
    }

    public function isClosed(): bool
    {
        return $this->status === TransactionStatus::Closed;
    }

    public function isPaid(): bool
    {
        return $this->status === TransactionStatus::PaymentReceived;
    }

    public function isFinished(): bool
    {
        return in_array(
            $this->status,
            [
                TransactionStatus::Closed,
                TransactionStatus::Cancelled,
            ],
            true
        );
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->vendor_id === $user->id
            || $this->buyer_id === $user->id;
    }

    // ─── State Machine ───────────────────────────────────────────

    public function canTransitionTo(TransactionStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }
}
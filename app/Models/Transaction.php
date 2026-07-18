<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        'secure_token',
        'status',
        'tracking_number',
        'shipping_proof_path',
        'payment_at',
        'shipped_at',
        'delivered_at',
        'closed_at',
    ];

    protected $casts = [
        'status'       => TransactionStatus::class,
        'amount'       => 'decimal:2',
        'payment_at'   => 'datetime',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function canTransitionTo(TransactionStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }
}
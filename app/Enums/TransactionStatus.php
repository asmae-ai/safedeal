<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentReceived = 'payment_received';
    case InShipping = 'in_shipping';
    case Delivered = 'delivered';
    case Closed = 'closed';
    case Dispute = 'dispute';
    case Resolved = 'resolved';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'En attente de paiement',
            self::PaymentReceived => 'Paiement reçu',
            self::InShipping => 'En cours de livraison',
            self::Delivered => 'Livré',
            self::Closed => 'Clôturé',
            self::Dispute => 'Litige ouvert',
            self::Resolved => 'Litige résolu',
            self::Refunded => 'Remboursé',
            self::Cancelled => 'Annulé',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::PaymentReceived, self::Cancelled],
            self::PaymentReceived => [self::InShipping],
            self::InShipping => [self::Delivered],
            self::Delivered => [self::Closed, self::Dispute],
            self::Dispute => [self::Resolved, self::Refunded],
            self::Resolved => [self::Closed],
            self::Closed,
            self::Refunded,
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }
}

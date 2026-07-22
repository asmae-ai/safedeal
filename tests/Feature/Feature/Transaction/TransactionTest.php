<?php

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeApprovedVendor(): User
{
    return User::factory()->create([
        'role'            => 'vendor',
        'identity_status' => 'approved',
    ]);
}

function makeBuyer(): User
{
    return User::factory()->create(['role' => 'buyer']);
}

function makeTransaction(array $overrides = []): Transaction
{
    return Transaction::factory()->create($overrides);
}

// ─── Créer une transaction ────────────────────────────────────────────────────

describe('POST /api/v1/transactions', function () {

    it('vendor approuvé peut créer une transaction', function () {
        $vendor = makeApprovedVendor();

        $response = $this->actingAs($vendor, 'api')->postJson('/api/v1/transactions', [
            'title'  => 'iPhone 14 Pro',
            'amount' => 1200.00,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending_payment')
            ->assertJsonStructure(['data' => ['secure_link']]);

        expect(Transaction::where('vendor_id', $vendor->id)->exists())->toBeTrue();
    });

    it('génère un secure_token unique', function () {
        $vendor = makeApprovedVendor();

        $this->actingAs($vendor, 'api')->postJson('/api/v1/transactions', [
            'title'  => 'Transaction 1',
            'amount' => 500,
        ]);

        $this->actingAs($vendor, 'api')->postJson('/api/v1/transactions', [
            'title'  => 'Transaction 2',
            'amount' => 800,
        ]);

        $tokens = Transaction::where('vendor_id', $vendor->id)->pluck('secure_token');
        expect($tokens->unique()->count())->toBe(2);
    });

    it('vendor non approuvé ne peut pas créer', function () {
        $vendor = User::factory()->create([
            'role'            => 'vendor',
            'identity_status' => 'pending',
        ]);

        $this->actingAs($vendor, 'api')->postJson('/api/v1/transactions', [
            'title'  => 'iPhone 14',
            'amount' => 1200,
        ])->assertStatus(403);
    });

    it('buyer ne peut pas créer une transaction', function () {
        $buyer = makeBuyer();

        $this->actingAs($buyer, 'api')->postJson('/api/v1/transactions', [
            'title'  => 'iPhone 14',
            'amount' => 1200,
        ])->assertStatus(403);
    });

    it('retourne 401 si non authentifié', function () {
        $this->postJson('/api/v1/transactions', [
            'title'  => 'iPhone 14',
            'amount' => 1200,
        ])->assertStatus(401);
    });

    it('valide le titre obligatoire', function () {
        $vendor = makeApprovedVendor();

        $this->actingAs($vendor, 'api')->postJson('/api/v1/transactions', [
            'amount' => 1200,
        ])->assertStatus(422)->assertJsonValidationErrors(['title']);
    });

    it('valide le montant minimum', function () {
        $vendor = makeApprovedVendor();

        $this->actingAs($vendor, 'api')->postJson('/api/v1/transactions', [
            'title'  => 'iPhone 14',
            'amount' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors(['amount']);
    });

    it('valide la devise', function () {
        $vendor = makeApprovedVendor();

        $this->actingAs($vendor, 'api')->postJson('/api/v1/transactions', [
            'title'    => 'iPhone 14',
            'amount'   => 1200,
            'currency' => 'GBP',
        ])->assertStatus(422)->assertJsonValidationErrors(['currency']);
    });
});

// ─── Consulter via lien public ────────────────────────────────────────────────

describe('GET /api/v1/transactions/{token}', function () {

    it('anyone peut consulter via le lien sécurisé', function () {
        $transaction = makeTransaction();

        $this->getJson("/api/v1/transactions/{$transaction->secure_token}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending_payment');
    });

    it('retourne 404 pour un token invalide', function () {
        $this->getJson('/api/v1/transactions/token-invalide')
            ->assertStatus(404);
    });
});

// ─── Lister ses transactions ──────────────────────────────────────────────────

describe('GET /api/v1/transactions', function () {

    it('vendor voit ses transactions', function () {
        $vendor = makeApprovedVendor();
        makeTransaction(['vendor_id' => $vendor->id]);
        makeTransaction(['vendor_id' => $vendor->id]);

        $this->actingAs($vendor, 'api')->getJson('/api/v1/transactions')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('ne voit pas les transactions des autres', function () {
        $vendor1 = makeApprovedVendor();
        $vendor2 = makeApprovedVendor();
        makeTransaction(['vendor_id' => $vendor2->id]);

        $this->actingAs($vendor1, 'api')->getJson('/api/v1/transactions')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('retourne 401 si non authentifié', function () {
        $this->getJson('/api/v1/transactions')->assertStatus(401);
    });
});

// ─── Annuler une transaction ──────────────────────────────────────────────────

describe('PATCH /api/v1/transactions/{id}/cancel', function () {

    it('vendor peut annuler sa transaction en pending_payment', function () {
        $vendor      = makeApprovedVendor();
        $transaction = makeTransaction([
            'vendor_id' => $vendor->id,
            'status'    => TransactionStatus::PendingPayment,
        ]);

        $this->actingAs($vendor, 'api')
            ->patchJson("/api/v1/transactions/{$transaction->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    });

    it('ne peut pas annuler si déjà payé', function () {
        $vendor      = makeApprovedVendor();
        $transaction = makeTransaction([
            'vendor_id' => $vendor->id,
            'status'    => TransactionStatus::PaymentReceived,
        ]);

        $this->actingAs($vendor, 'api')
            ->patchJson("/api/v1/transactions/{$transaction->id}/cancel")
            ->assertStatus(403);
    });

    it('un autre vendor ne peut pas annuler', function () {
        $vendor1     = makeApprovedVendor();
        $vendor2     = makeApprovedVendor();
        $transaction = makeTransaction(['vendor_id' => $vendor1->id]);

        $this->actingAs($vendor2, 'api')
            ->patchJson("/api/v1/transactions/{$transaction->id}/cancel")
            ->assertStatus(403);
    });

    it('retourne 401 si non authentifié', function () {
        $transaction = makeTransaction();

        $this->patchJson("/api/v1/transactions/{$transaction->id}/cancel")
            ->assertStatus(401);
    });
});

// ─── State Machine ────────────────────────────────────────────────────────────

describe('State Machine TransactionStatus', function () {

    it('pending_payment peut aller vers payment_received', function () {
        $status = TransactionStatus::PendingPayment;
        expect($status->canTransitionTo(TransactionStatus::PaymentReceived))->toBeTrue();
    });

    it('pending_payment peut être annulé', function () {
        $status = TransactionStatus::PendingPayment;
        expect($status->canTransitionTo(TransactionStatus::Cancelled))->toBeTrue();
    });

    it('closed ne peut pas changer', function () {
        $status = TransactionStatus::Closed;
        expect($status->canTransitionTo(TransactionStatus::Cancelled))->toBeFalse();
    });

    it('delivered peut aller vers dispute', function () {
        $status = TransactionStatus::Delivered;
        expect($status->canTransitionTo(TransactionStatus::Dispute))->toBeTrue();
    });
});
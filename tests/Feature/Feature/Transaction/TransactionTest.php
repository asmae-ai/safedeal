<?php

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('vendor can create transaction', function () {
    $vendor = User::factory()->create([
        'role' => 'vendor',
        'identity_status' => 'approved',
        'email_verified_at' => now(),
    ]);

    actingAs($vendor, 'api')->postJson('/api/v1/transactions', [
        'title' => 'MacBook Pro M3',
        'amount' => 15000.00,
        'currency' => 'MAD',
        'description' => 'Ordinateur portable en excellent état',
    ])
        ->assertStatus(201)
        ->assertJsonPath('data.title', 'MacBook Pro M3')
        ->assertJsonPath('data.status', TransactionStatus::PendingPayment->value)
        ->assertJsonPath('data.vendor.id', $vendor->id);

    assertDatabaseHas('transactions', [
        'title' => 'MacBook Pro M3',
        'vendor_id' => $vendor->id,
        'status' => TransactionStatus::PendingPayment->value,
    ]);
});

test('authorized user can view transaction by secure token', function () {
    $vendor = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'vendor_id' => $vendor->id,
        'secure_token' => 'secure-token-abc-123',
        'status' => TransactionStatus::PendingPayment,
    ]);

    actingAs($vendor, 'api')
        ->getJson("/api/v1/transactions/{$transaction->secure_token}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $transaction->id);
});

test('user can list their transactions', function () {
    $vendor = User::factory()->create();
    Transaction::factory()->count(3)->create([
        'vendor_id' => $vendor->id,
    ]);

    Transaction::factory()->count(2)->create();

    actingAs($vendor, 'api')
        ->getJson('/api/v1/transactions')
        ->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'total'],
        ]);
});

test('buyer can pay transaction', function () {
    $vendor = User::factory()->create();
    $buyer = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'vendor_id' => $vendor->id,
        'buyer_id' => $buyer->id,
        'status' => TransactionStatus::PendingPayment,
    ]);

    actingAs($buyer, 'api')
        ->postJson("/api/v1/transactions/{$transaction->id}/pay")
        ->assertStatus(200)
        ->assertJsonPath('data.status', TransactionStatus::PaymentReceived->value);

    expect($transaction->fresh()->paid_at)->not->toBeNull();
});

test('vendor can ship transaction', function () {
    $vendor = User::factory()->create();
    $buyer = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'vendor_id' => $vendor->id,
        'buyer_id' => $buyer->id,
        'status' => TransactionStatus::PaymentReceived,
    ]);

    actingAs($vendor, 'api')
        ->postJson("/api/v1/transactions/{$transaction->id}/ship")
        ->assertStatus(200)
        ->assertJsonPath('data.status', TransactionStatus::InShipping->value);

    expect($transaction->fresh()->shipped_at)->not->toBeNull();
});

test('buyer can deliver transaction', function () {
    $vendor = User::factory()->create();
    $buyer = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'vendor_id' => $vendor->id,
        'buyer_id' => $buyer->id,
        'status' => TransactionStatus::InShipping,
    ]);

    actingAs($buyer, 'api')
        ->postJson("/api/v1/transactions/{$transaction->id}/deliver")
        ->assertStatus(200)
        ->assertJsonPath('data.status', TransactionStatus::Delivered->value);

    expect($transaction->fresh()->delivered_at)->not->toBeNull();
});

test('vendor can close transaction', function () {
    $vendor = User::factory()->create();
    $buyer = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'vendor_id' => $vendor->id,
        'buyer_id' => $buyer->id,
        'status' => TransactionStatus::Delivered,
    ]);

    actingAs($vendor, 'api')
        ->postJson("/api/v1/transactions/{$transaction->id}/close")
        ->assertStatus(200)
        ->assertJsonPath('data.status', TransactionStatus::Closed->value);

    expect($transaction->fresh()->closed_at)->not->toBeNull();
});

test('invalid state transition throws unprocessable entity error', function () {
    $vendor = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'vendor_id' => $vendor->id,
        'status' => TransactionStatus::PendingPayment,
    ]);

    actingAs($vendor, 'api')
        ->postJson("/api/v1/transactions/{$transaction->id}/ship")
        ->assertStatus(422);
});

test('transaction can be cancelled', function () {
    $vendor = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'vendor_id' => $vendor->id,
        'status' => TransactionStatus::PendingPayment,
    ]);

    actingAs($vendor, 'api')
        ->patchJson("/api/v1/transactions/{$transaction->id}/cancel")
        ->assertStatus(200)
        ->assertJsonPath('data.status', TransactionStatus::Cancelled->value);

    assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'status' => TransactionStatus::Cancelled->value,
    ]);
});

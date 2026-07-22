<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

it('vendor can submit identity verification', function (): void {
    $vendor = User::factory()->create(['role' => UserRole::VENDOR]);

    $response = $this->actingAs($vendor, 'api')->postJson('/api/v1/verify-identity', [
        'id_document'      => UploadedFile::fake()->create('cin.pdf', 1024, 'application/pdf'),
        'id_document_type' => 'cin',
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('verification_status', 'pending');
});

it('buyer cannot submit identity verification', function (): void {
    $buyer = User::factory()->create(['role' => UserRole::BUYER]);

    $response = $this->actingAs($buyer, 'api')->postJson('/api/v1/verify-identity', [
        'id_document'      => UploadedFile::fake()->create('cin.pdf', 1024, 'application/pdf'),
        'id_document_type' => 'cin',
    ]);

    $response->assertStatus(403);
});

it('vendor cannot submit twice while pending', function (): void {
    $vendor = User::factory()->create(['role' => UserRole::VENDOR]);

    $this->actingAs($vendor, 'api')->postJson('/api/v1/verify-identity', [
        'id_document'      => UploadedFile::fake()->create('cin.pdf', 1024, 'application/pdf'),
        'id_document_type' => 'cin',
    ]);

    $response = $this->actingAs($vendor, 'api')->postJson('/api/v1/verify-identity', [
        'id_document'      => UploadedFile::fake()->create('cin.pdf', 1024, 'application/pdf'),
        'id_document_type' => 'cin',
    ]);

    $response->assertStatus(409);
});

it('returns not_submitted when no verification exists', function (): void {
    $vendor = User::factory()->create(['role' => UserRole::VENDOR]);

    $response = $this->actingAs($vendor, 'api')->getJson('/api/v1/verify-identity/status');

    $response->assertStatus(200)
             ->assertJsonPath('verification_status', 'not_submitted');
});

it('returns pending status after submission', function (): void {
    $vendor = User::factory()->create(['role' => UserRole::VENDOR]);

    $this->actingAs($vendor, 'api')->postJson('/api/v1/verify-identity', [
        'id_document'      => UploadedFile::fake()->create('cin.pdf', 1024, 'application/pdf'),
        'id_document_type' => 'cin',
    ]);

    $response = $this->actingAs($vendor, 'api')->getJson('/api/v1/verify-identity/status');

    $response->assertStatus(200)
             ->assertJsonPath('verification_status', 'pending');
});

it('rejects file larger than 5mb', function (): void {
    $vendor = User::factory()->create(['role' => UserRole::VENDOR]);

    $response = $this->actingAs($vendor, 'api')->postJson('/api/v1/verify-identity', [
        'id_document'      => UploadedFile::fake()->create('cin.pdf', 6000, 'application/pdf'),
        'id_document_type' => 'cin',
    ]);

    $response->assertStatus(422);
});

it('rejects invalid document type', function (): void {
    $vendor = User::factory()->create(['role' => UserRole::VENDOR]);

    $response = $this->actingAs($vendor, 'api')->postJson('/api/v1/verify-identity', [
        'id_document'      => UploadedFile::fake()->create('cin.pdf', 1024, 'application/pdf'),
        'id_document_type' => 'driving_license',
    ]);

    $response->assertStatus(422);
});

it('unauthenticated user cannot submit verification', function (): void {
    $response = $this->postJson('/api/v1/verify-identity', [
        'id_document'      => UploadedFile::fake()->create('cin.pdf', 1024, 'application/pdf'),
        'id_document_type' => 'cin',
    ]);

    $response->assertStatus(401);
});
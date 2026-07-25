<?php

use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

it('sends verification code on register', function () {
    Notification::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'role' => 'vendor',
    ])->assertStatus(201);

    $user = User::where('email', 'test@example.com')->first();
    Notification::assertSentTo($user, EmailVerificationNotification::class);
});

it('registered user has unverified email', function () {
    Notification::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'role' => 'vendor',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect($user->email_verified_at)->toBeNull();
});

it('unverified user cannot login', function () {
    Notification::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'role' => 'vendor',
    ]);

    $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'Password123',

    ])->assertStatus(403)
        ->assertJsonPath('message', 'Your email address is not verified.');
});
it('user can verify email with valid code', function () {
    Notification::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'role' => 'vendor',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    $code = null;
    Notification::assertSentTo($user, EmailVerificationNotification::class, function ($n) use (&$code) {
        $code = $n->getCode();

        return true;
    });

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/email/verify', ['code' => $code])
        ->assertStatus(200)
        ->assertJsonPath('message', 'Email verified successfully.');

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('user cannot verify with invalid code', function () {
    Notification::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'role' => 'vendor',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/email/verify', ['code' => '000000'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Invalid or expired verification code.');
});

it('verified user can login', function () {
    Notification::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'role' => 'vendor',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    $code = null;
    Notification::assertSentTo($user, EmailVerificationNotification::class, function ($n) use (&$code) {
        $code = $n->getCode();

        return true;
    });

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/email/verify', ['code' => $code]);

    $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'Password123',
    ])->assertStatus(200)
        ->assertJsonStructure(['user', 'token']);
});

it('user can resend verification code', function () {
    Notification::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'role' => 'vendor',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    Redis::flushdb(); // Reset cooldown

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/email/resend')
        ->assertStatus(200)
        ->assertJsonPath('message', 'Verification code sent.');

    Notification::assertSentTo($user, EmailVerificationNotification::class);
});

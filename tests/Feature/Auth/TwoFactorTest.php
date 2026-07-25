<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

it('authenticated user can request OTP', function () {
    Notification::fake();

    $user = User::factory()->create(['role' => UserRole::VENDOR]);

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/2fa/send')
        ->assertStatus(200)
        ->assertJsonPath('message', 'OTP sent to your email address.');

    Notification::assertSentTo($user, OtpNotification::class);
});

it('unauthenticated user cannot request OTP', function () {
    $this->postJson('/api/v1/auth/2fa/send')
        ->assertStatus(401);
});

it('user cannot request OTP twice within cooldown', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user, 'api')->postJson('/api/v1/auth/2fa/send');

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/2fa/send')
        ->assertStatus(429);
});

it('user can verify valid OTP', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user, 'api')->postJson('/api/v1/auth/2fa/send');

    $otp = null;
    Notification::assertSentTo($user, OtpNotification::class, function ($notification) use (&$otp) {
        $otp = $notification->getOtp();

        return true;
    });

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/2fa/verify', ['code' => $otp])
        ->assertStatus(200)
        ->assertJsonStructure(['message', 'verified_at']);
});

it('user cannot verify invalid OTP', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user, 'api')->postJson('/api/v1/auth/2fa/send');

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/2fa/verify', ['code' => '000000'])
        ->assertStatus(422);
});

it('user cannot verify expired OTP', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/2fa/verify', ['code' => '123456'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Code OTP expiré.');
});

it('validates OTP code format', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'api')
        ->postJson('/api/v1/auth/2fa/verify', ['code' => 'abcdef'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

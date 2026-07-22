<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use App\Notifications\PasswordResetNotification;

it('user can request password reset', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'test@example.com',
    ])->assertStatus(200)
      ->assertJsonPath('message', 'If this email exists, a reset link has been sent.');

    Notification::assertSentTo($user, PasswordResetNotification::class);
});

it('forgot password returns same message for unknown email', function () {
    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'unknown@example.com',
    ])->assertStatus(200)
      ->assertJsonPath('message', 'If this email exists, a reset link has been sent.');
});

it('forgot password validates email format', function () {
    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'not-an-email',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['email']);
});

it('user can reset password with valid token', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'test@example.com',
    ]);

    $token = null;
    Notification::assertSentTo($user, PasswordResetNotification::class, function ($notification) use (&$token) {
        $token = $notification->getToken();
        return true;
    });

    $this->postJson('/api/v1/auth/password/reset', [
        'email'                 => 'test@example.com',
        'token'                 => $token,
        'password'              => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ])->assertStatus(200)
      ->assertJsonPath('message', 'Password reset successfully.');

    $user->refresh();
    expect(Hash::check('NewPassword123', $user->password))->toBeTrue();
});

it('reset password fails with invalid token', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/v1/auth/password/reset', [
        'email'                 => 'test@example.com',
        'token'                 => str_repeat('a', 64),
        'password'              => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ])->assertStatus(422)
      ->assertJsonPath('message', 'Invalid or expired reset token.');
});

it('reset password validates password confirmation', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/v1/auth/password/reset', [
        'email'                 => 'test@example.com',
        'token'                 => str_repeat('a', 64),
        'password'              => 'NewPassword123',
        'password_confirmation' => 'Different123',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['password']);
});
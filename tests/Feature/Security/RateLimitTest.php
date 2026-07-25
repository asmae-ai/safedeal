<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

it('blocks login after 5 failed attempts', function (): void {
    RateLimiter::clear('login');

    User::factory()->create([
        'email' => 'victim@example.com',
        'password' => bcrypt('correctpassword'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/login', [
            'email' => 'victim@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    $response = $this->postJson('/api/v1/login', [
        'email' => 'victim@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429);
});

it('blocks register after 10 attempts', function (): void {
    RateLimiter::clear('register');

    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => "user{$i}@example.com",
            'password' => 'Password123',
            'role' => 'buyer',
        ]);
    }

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'user99@example.com',
        'password' => 'Password123',
        'role' => 'buyer',
    ]);

    $response->assertStatus(429);
});

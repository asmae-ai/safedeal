<?php

use App\Models\User;
use App\Enums\UserRole;

it('can register a new vendor', function () {
    $response = $this->postJson('/api/v1/register', [
        'name'     => 'Youssef Amrani',
        'email'    => 'youssef@example.com',
        'password' => 'Password123',
        'role'     => 'vendor',
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['user', 'token'])
             ->assertJsonPath('user.role', 'vendor');
});

it('cannot register with duplicate email', function () {
    User::factory()->create(['email' => 'youssef@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name'     => 'Youssef Amrani',
        'email'    => 'youssef@example.com',
        'password' => 'Password123',
        'role'     => 'vendor',
    ]);

    $response->assertStatus(422);
});

it('cannot register with invalid role', function () {
    $response = $this->postJson('/api/v1/register', [
        'name'     => 'Youssef Amrani',
        'email'    => 'youssef@example.com',
        'password' => 'Password123',
        'role'     => 'superadmin',
    ]);

    $response->assertStatus(422);
});

it('can login with valid credentials', function () {
    User::factory()->create([
        'email'    => 'youssef@example.com',
        'password' => bcrypt('Password123'),
        'role'     => UserRole::VENDOR,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email'    => 'youssef@example.com',
        'password' => 'Password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure(['user', 'token']);
});

it('cannot login with wrong password', function () {
    User::factory()->create([
        'email'    => 'youssef@example.com',
        'password' => bcrypt('Password123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email'    => 'youssef@example.com',
        'password' => 'WrongPassword',
    ]);

    $response->assertStatus(401);
});

it('can get authenticated user', function () {
    $user = User::factory()->create(['role' => UserRole::BUYER]);

    $response = $this->actingAs($user)->getJson('/api/v1/me');

    $response->assertStatus(200)
             ->assertJsonPath('role', 'buyer');
});

it('can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/logout');

    $response->assertStatus(200)
             ->assertJsonPath('message', 'Logged out successfully');
});
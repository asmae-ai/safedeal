<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Client;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Client::forceCreate([
            'id'            => '9f853b00-0001-0000-0000-000000000001',
            'name'          => 'Test Personal Access Client',
            'secret'        => null,
            'provider'      => 'users',
            'redirect_uris' => [],
            'grant_types'   => ['personal_access'],
            'revoked'       => false,
        ]);
    }

    protected function actingAsUser(User $user): static
    {
        return $this->actingAs($user, 'api');
    }
}
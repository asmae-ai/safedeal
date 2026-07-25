<?php

it('returns security headers on api responses', function (): void {
    $response = $this->getJson('/api/v1/me');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('returns 401 for unauthenticated requests', function (): void {
    $response = $this->getJson('/api/v1/me');

    $response->assertStatus(401)
        ->assertJsonStructure(['message']);
});

it('returns json error format for invalid routes', function (): void {
    $response = $this->getJson('/api/v1/nonexistent-route');

    $response->assertStatus(404)
        ->assertJsonStructure(['message']);
});

it('does not expose server technology headers', function (): void {
    $response = $this->getJson('/api/v1/me');

    $this->assertNull($response->headers->get('X-Powered-By'));
});

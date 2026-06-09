<?php

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function signedAgentRequest(Agent $agent, string $secretKey, string $method, string $path, string $body = ''): array
{
    $timestamp = (string) time();
    $bodyHash = hash('sha256', $body);
    $canonical = implode("\n", [strtoupper($method), $path, $timestamp, $bodyHash]);
    $signature = base64_encode(sodium_crypto_sign_detached($canonical, $secretKey));

    return [
        'X-Agent-ID' => $agent->id,
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
    ];
}

it('returns 401 when authentication headers are missing', function () {
    $agent = Agent::factory()->create();

    $this->getJson("/api/v1/agents/{$agent->id}/config")
        ->assertUnauthorized();
});

it('returns the agent config for an authenticated agent', function () {
    $keypair = sodium_crypto_sign_keypair();
    $secretKey = sodium_crypto_sign_secretkey($keypair);
    $agent = Agent::factory()->create([
        'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
        'status' => 'active',
    ]);

    $path = "/api/v1/agents/{$agent->id}/config";
    $headers = signedAgentRequest($agent, $secretKey, 'GET', $path);

    // Use get() not getJson() — getJson sends '[]' as body, breaking the body hash in the signature
    $this->withHeaders($headers)
        ->get($path)
        ->assertOk()
        ->assertJsonStructure(['version', 'revoked', 'checks', 'intervals']);
});

it('activates a pending agent on first config fetch', function () {
    $keypair = sodium_crypto_sign_keypair();
    $secretKey = sodium_crypto_sign_secretkey($keypair);
    $agent = Agent::factory()->pending()->create([
        'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
    ]);

    $path = "/api/v1/agents/{$agent->id}/config";
    $headers = signedAgentRequest($agent, $secretKey, 'GET', $path);

    $this->withHeaders($headers)->get($path)->assertOk();

    expect($agent->fresh()->status)->toBe('active');
});

it('returns 401 when the request timestamp is expired', function () {
    $agent = Agent::factory()->create();

    $this->withHeaders([
        'X-Agent-ID' => $agent->id,
        'X-Timestamp' => (string) (time() - 120),
        'X-Signature' => 'fakesig',
    ])->get("/api/v1/agents/{$agent->id}/config")
        ->assertUnauthorized();
});

it('returns 401 when the agent does not exist', function () {
    $uuid = '00000000-0000-0000-0000-000000000000';

    $this->withHeaders([
        'X-Agent-ID' => $uuid,
        'X-Timestamp' => (string) time(),
        'X-Signature' => 'fakesig',
    ])->get("/api/v1/agents/{$uuid}/config")
        ->assertUnauthorized();
});

it('returns 401 when the signature has invalid base64 encoding', function () {
    $keypair = sodium_crypto_sign_keypair();
    $agent = Agent::factory()->create([
        'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
        'status' => 'active',
    ]);

    $this->withHeaders([
        'X-Agent-ID' => $agent->id,
        'X-Timestamp' => (string) time(),
        'X-Signature' => '!!!not_valid_base64!!!',
    ])->get("/api/v1/agents/{$agent->id}/config")
        ->assertUnauthorized();
});

it('returns 401 when the signature does not match the agent public key', function () {
    $storedKeypair = sodium_crypto_sign_keypair();
    $signingKeypair = sodium_crypto_sign_keypair();
    $agent = Agent::factory()->create([
        'public_key' => base64_encode(sodium_crypto_sign_publickey($storedKeypair)),
        'status' => 'active',
    ]);

    $path = "/api/v1/agents/{$agent->id}/config";
    $headers = signedAgentRequest($agent, sodium_crypto_sign_secretkey($signingKeypair), 'GET', $path);

    $this->withHeaders($headers)->get($path)->assertUnauthorized();
});

it('returns 403 for a revoked agent', function () {
    $keypair = sodium_crypto_sign_keypair();
    $secretKey = sodium_crypto_sign_secretkey($keypair);
    $agent = Agent::factory()->revoked()->create([
        'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
    ]);

    $path = "/api/v1/agents/{$agent->id}/config";
    $headers = signedAgentRequest($agent, $secretKey, 'GET', $path);

    $this->withHeaders($headers)->get($path)->assertForbidden();
});

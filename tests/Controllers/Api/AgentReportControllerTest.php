<?php

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function signedAgentPostRequest(Agent $agent, string $secretKey, string $path, array $payload): array
{
    $body = json_encode($payload);
    $timestamp = (string) time();
    $bodyHash = hash('sha256', $body);
    $canonical = implode("\n", ['POST', $path, $timestamp, $bodyHash]);
    $signature = base64_encode(sodium_crypto_sign_detached($canonical, $secretKey));

    return [
        'X-Agent-ID' => $agent->id,
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
    ];
}

it('returns 401 when authentication headers are missing', function () {
    $agent = Agent::factory()->create();

    $this->postJson("/api/v1/agents/{$agent->id}/reports", [])
        ->assertUnauthorized();
});

it('stores a report for an authenticated agent', function () {
    $keypair = sodium_crypto_sign_keypair();
    $secretKey = sodium_crypto_sign_secretkey($keypair);
    $agent = Agent::factory()->create([
        'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
    ]);

    $path = "/api/v1/agents/{$agent->id}/reports";
    $payload = [
        'hostname' => 'test.local',
        'timestamp' => now()->toIso8601String(),
        'checks' => [
            ['name' => 'php', 'status' => 'ok', 'message' => 'PHP running'],
        ],
    ];

    $headers = signedAgentPostRequest($agent, $secretKey, $path, $payload);

    $this->withHeaders($headers)
        ->postJson($path, $payload)
        ->assertCreated()
        ->assertJson(['ok' => true]);

    $this->assertDatabaseHas('agent_reports', ['agent_id' => $agent->id]);
});

it('stores check results with the report', function () {
    $keypair = sodium_crypto_sign_keypair();
    $secretKey = sodium_crypto_sign_secretkey($keypair);
    $agent = Agent::factory()->create([
        'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
    ]);

    $path = "/api/v1/agents/{$agent->id}/reports";
    $payload = [
        'hostname' => 'test.local',
        'checks' => [
            ['name' => 'php',   'status' => 'ok',       'message' => 'OK'],
            ['name' => 'mysql', 'status' => 'critical',  'message' => 'Connection refused'],
        ],
    ];

    $headers = signedAgentPostRequest($agent, $secretKey, $path, $payload);

    $this->withHeaders($headers)->postJson($path, $payload)->assertCreated();

    $this->assertDatabaseHas('agent_check_results', ['name' => 'mysql', 'status' => 'critical']);
});

it('uses per-check timestamp when provided', function () {
    $keypair = sodium_crypto_sign_keypair();
    $secretKey = sodium_crypto_sign_secretkey($keypair);
    $agent = Agent::factory()->create([
        'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
    ]);

    $checkTime = now()->subMinutes(2)->toIso8601String();
    $path = "/api/v1/agents/{$agent->id}/reports";
    $payload = [
        'hostname' => 'test.local',
        'checks' => [
            ['name' => 'php', 'status' => 'ok', 'message' => 'OK', 'timestamp' => $checkTime],
        ],
    ];

    $headers = signedAgentPostRequest($agent, $secretKey, $path, $payload);

    $this->withHeaders($headers)->postJson($path, $payload)->assertCreated();

    $this->assertDatabaseHas('agent_check_results', ['name' => 'php']);
});

it('updates the agent version and binary hash from the report', function () {
    $keypair = sodium_crypto_sign_keypair();
    $secretKey = sodium_crypto_sign_secretkey($keypair);
    $agent = Agent::factory()->create([
        'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
    ]);

    $path = "/api/v1/agents/{$agent->id}/reports";
    $payload = [
        'hostname' => 'test.local',
        'version' => 'v1.2.3',
        'binary_hash' => 'abc123',
        'checks' => [],
    ];

    $headers = signedAgentPostRequest($agent, $secretKey, $path, $payload);

    $this->withHeaders($headers)->postJson($path, $payload)->assertCreated();

    expect($agent->fresh())
        ->agent_version->toBe('v1.2.3')
        ->reported_binary_hash->toBe('abc123');
});

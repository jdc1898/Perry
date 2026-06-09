<?php

use App\Models\Agent;
use App\Models\AgentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeAgent(array $attrs = []): Agent
{
    return Agent::create(array_merge([
        'name' => 'Test Agent',
        'public_key' => 'public-key',
        'fingerprint' => uniqid('fp_'),
    ], $attrs));
}

it('detects a revoked agent', function () {
    $agent = makeAgent(['status' => 'revoked']);
    expect($agent->isRevoked())->toBeTrue();
});

it('is not revoked when status is active', function () {
    $agent = makeAgent(['status' => 'active']);
    expect($agent->isRevoked())->toBeFalse();
});

it('is online when last seen within 10 minutes', function () {
    $agent = makeAgent(['last_seen_at' => now()->subMinutes(5)]);
    expect($agent->isOnline())->toBeTrue();
});

it('is offline when last seen more than 10 minutes ago', function () {
    $agent = makeAgent(['last_seen_at' => now()->subMinutes(11)]);
    expect($agent->isOnline())->toBeFalse();
});

it('is offline when last_seen_at is null', function () {
    $agent = makeAgent(['last_seen_at' => null]);
    expect($agent->isOnline())->toBeFalse();
});

it('has a reports relationship returning the latest report', function () {
    $agent = makeAgent();
    AgentReport::create(['agent_id' => $agent->id, 'hostname' => 'host', 'reported_at' => now()->subMinutes(2)]);
    AgentReport::create(['agent_id' => $agent->id, 'hostname' => 'host', 'reported_at' => now()]);
    expect($agent->reports)->toBeInstanceOf(AgentReport::class);
});

it('has an allReports relationship ordered by most recent first', function () {
    $agent = makeAgent();
    AgentReport::create(['agent_id' => $agent->id, 'hostname' => 'host', 'reported_at' => now()->subMinutes(2)]);
    AgentReport::create(['agent_id' => $agent->id, 'hostname' => 'host', 'reported_at' => now()->subMinutes(1)]);
    expect($agent->allReports)->toHaveCount(2);
});

it('returns remote config with default checks when config columns are null', function () {
    $agent = makeAgent();
    $config = $agent->toRemoteConfig();

    expect($config)->toHaveKeys(['version', 'revoked', 'auto_update', 'binary_hash', 'checks', 'intervals'])
        ->and($config['checks']['php'])->toBe(['enabled' => false, 'fpm_socket' => '', 'status_url' => ''])
        ->and($config['checks']['mysql'])->toBe(['enabled' => false, 'dsn' => '', 'check_replication' => false])
        ->and($config['checks']['reverb'])->toBe(['enabled' => false, 'host' => '127.0.0.1', 'port' => 8080])
        ->and($config['checks']['redis'])->toBe(['enabled' => false, 'addr' => '127.0.0.1:6379', 'password' => '', 'db' => 0])
        ->and($config['checks']['system'])->toBe([
            'enabled' => false,
            'disk_paths' => [],
            'network_interfaces' => [],
            'cpu_warn_pct' => 0,
            'ram_warn_pct' => 0,
            'disk_warn_pct' => 0,
        ]);
});

it('uses stored config in remote config when columns are set', function () {
    $phpConfig = ['enabled' => true, 'fpm_socket' => '/run/php/php8.2-fpm.sock', 'status_url' => 'http://localhost/status'];
    $agent = makeAgent(['php_config' => $phpConfig]);
    $config = $agent->toRemoteConfig();

    expect($config['checks']['php'])->toBe($phpConfig);
});

it('includes binary hash from storage in remote config', function () {
    Storage::fake('local');
    Storage::disk('local')->put('perry.sha256', 'abc123hash');

    $agent = makeAgent();
    $config = $agent->toRemoteConfig();

    expect($config['binary_hash'])->toBe('abc123hash');
});

it('returns empty binary hash when file does not exist', function () {
    Storage::fake('local');
    $agent = makeAgent();
    $config = $agent->toRemoteConfig();

    expect($config['binary_hash'])->toBe('');
});

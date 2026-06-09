<?php

use App\Models\Agent;
use App\Models\AgentCheckResult;
use App\Models\AgentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCheckResult(array $attrs = []): AgentCheckResult
{
    $agent = Agent::create([
        'name' => 'Test Agent',
        'public_key' => 'public-key',
        'fingerprint' => uniqid('fp_'),
    ]);

    $report = AgentReport::create([
        'agent_id' => $agent->id,
        'hostname' => 'test-host',
        'reported_at' => now(),
    ]);

    return AgentCheckResult::create(array_merge([
        'report_id' => $report->id,
        'name' => 'php',
        'status' => 'ok',
        'message' => 'PHP is healthy',
        'checked_at' => now(),
    ], $attrs));
}

it('belongs to a report', function () {
    $result = makeCheckResult();
    expect($result->report)->toBeInstanceOf(AgentReport::class);
});

it('casts metrics as an array', function () {
    $result = makeCheckResult(['metrics' => ['cpu' => 12.5, 'ram' => 80]]);
    expect($result->metrics)->toBeArray()
        ->and($result->metrics['cpu'])->toBe(12.5);
});

it('metrics is null when not set', function () {
    $result = makeCheckResult(['metrics' => null]);
    expect($result->metrics)->toBeNull();
});

it('casts checked_at as a datetime', function () {
    $result = makeCheckResult();
    expect($result->checked_at)->toBeInstanceOf(DateTimeInterface::class);
});

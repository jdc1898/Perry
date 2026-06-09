<?php

use App\Models\Agent;
use App\Models\AgentCheckResult;
use App\Models\AgentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeReport(array $attrs = []): AgentReport
{
    $agent = Agent::create([
        'name' => 'Test Agent',
        'public_key' => 'public-key',
        'fingerprint' => uniqid('fp_'),
    ]);

    return AgentReport::create(array_merge([
        'agent_id' => $agent->id,
        'hostname' => 'test-host',
        'reported_at' => now(),
    ], $attrs));
}

function addResult(AgentReport $report, string $status): AgentCheckResult
{
    return AgentCheckResult::create([
        'report_id' => $report->id,
        'name' => 'php',
        'status' => $status,
        'message' => 'ok',
        'checked_at' => now(),
    ]);
}

it('belongs to an agent', function () {
    $report = makeReport();
    expect($report->agent)->toBeInstanceOf(Agent::class);
});

it('has a checkResults relationship', function () {
    $report = makeReport();
    addResult($report, 'ok');
    addResult($report, 'warning');
    expect($report->checkResults)->toHaveCount(2);
});

it('overall status is ok when all results are ok', function () {
    $report = makeReport();
    addResult($report, 'ok');
    addResult($report, 'ok');
    $report->load('checkResults');

    expect($report->overallStatus())->toBe('ok');
});

it('overall status is warning when any result is warning', function () {
    $report = makeReport();
    addResult($report, 'ok');
    addResult($report, 'warning');
    $report->load('checkResults');

    expect($report->overallStatus())->toBe('warning');
});

it('overall status is critical when any result is critical', function () {
    $report = makeReport();
    addResult($report, 'ok');
    addResult($report, 'warning');
    addResult($report, 'critical');
    $report->load('checkResults');

    expect($report->overallStatus())->toBe('critical');
});

it('overall status ignores unknown results', function () {
    $report = makeReport();
    addResult($report, 'unknown');
    $report->load('checkResults');

    expect($report->overallStatus())->toBe('ok');
});

it('overall status is ok with no check results', function () {
    $report = makeReport();
    $report->load('checkResults');

    expect($report->overallStatus())->toBe('ok');
});

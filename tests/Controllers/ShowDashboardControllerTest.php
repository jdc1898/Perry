<?php

use App\Models\Agent;
use App\Models\AgentCheckResult;
use App\Models\AgentReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('allows authenticated users to view the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

it('renders the dashboard with agents but no reports', function () {
    Agent::factory()->count(2)->create(['status' => 'active']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

it('renders the dashboard with agents that have recent reports', function () {
    $agent = Agent::factory()->create(['status' => 'active']);
    $report = AgentReport::factory()->create([
        'agent_id' => $agent->id,
        'reported_at' => now()->subMinutes(10),
    ]);
    AgentCheckResult::factory()->ok()->create(['report_id' => $report->id, 'checked_at' => now()->subMinutes(10)]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

it('renders the dashboard with two reports in the same uptime slot', function () {
    $agent = Agent::factory()->create(['status' => 'active']);
    $time = now()->subMinutes(5);

    $r1 = AgentReport::factory()->create(['agent_id' => $agent->id, 'reported_at' => $time]);
    AgentCheckResult::factory()->ok()->create(['report_id' => $r1->id, 'checked_at' => $time]);

    $r2 = AgentReport::factory()->create(['agent_id' => $agent->id, 'reported_at' => $time->copy()->addSeconds(30)]);
    AgentCheckResult::factory()->critical()->create(['report_id' => $r2->id, 'checked_at' => $time->copy()->addSeconds(30)]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

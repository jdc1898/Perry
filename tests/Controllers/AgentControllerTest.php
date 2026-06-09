<?php

use App\Models\Agent;
use App\Models\AgentCheckResult;
use App\Models\AgentReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shows the agent list', function () {
    Agent::factory()->count(2)->create();

    $this->get(route('agents.index'))->assertOk();
});

it('shows the create agent form', function () {
    $this->get(route('agents.create'))->assertOk();
});

it('stores a new agent', function () {
    $keypair = sodium_crypto_sign_keypair();
    $data = [
        'id' => fake()->uuid(),
        'name' => 'Test Agent',
        'public_key' => base64_encode(sodium_crypto_sign_publickey($keypair)),
        'fingerprint' => fake()->sha256(),
    ];

    $this->post(route('agents.store'), $data)->assertRedirect(route('agents.index'));

    $this->assertDatabaseHas('agents', ['name' => 'Test Agent']);
});

it('shows agent details', function () {
    $agent = Agent::factory()->create();

    $this->get(route('agents.show', $agent))->assertOk();
});

it('renders agent timeline with reports', function () {
    $agent = Agent::factory()->create();
    $report = AgentReport::factory()->create([
        'agent_id' => $agent->id,
        'reported_at' => now()->subDays(2),
    ]);
    AgentCheckResult::factory()->ok()->create(['report_id' => $report->id]);

    $this->get(route('agents.show', $agent))->assertOk();
});

it('calls worstStatus when two reports fall in the same slot', function () {
    $agent = Agent::factory()->create();
    $time = now()->subDays(1)->setTime(10, 0, 0);

    $r1 = AgentReport::factory()->create(['agent_id' => $agent->id, 'reported_at' => $time]);
    AgentCheckResult::factory()->ok()->create(['report_id' => $r1->id]);

    $r2 = AgentReport::factory()->create(['agent_id' => $agent->id, 'reported_at' => $time->copy()->addSeconds(30)]);
    AgentCheckResult::factory()->critical()->create(['report_id' => $r2->id]);

    $this->get(route('agents.show', $agent))->assertOk();
});

it('updates agent configuration', function () {
    $agent = Agent::factory()->create();

    $this->put(route('agents.update', $agent), [
        'name' => 'Updated Name',
        'check_interval' => 120,
        'config_poll_interval' => 600,
        'auto_update' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('agents', ['id' => $agent->id, 'name' => 'Updated Name']);
});

it('deletes an agent', function () {
    $agent = Agent::factory()->create();

    $this->delete(route('agents.destroy', $agent))->assertRedirect(route('agents.index'));

    $this->assertDatabaseMissing('agents', ['id' => $agent->id]);
});

it('redirects guests attempting to view agents', function () {
    auth()->logout();

    $this->get(route('agents.index'))->assertRedirect(route('login'));
});

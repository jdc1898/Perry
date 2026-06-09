<?php

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('revokes an active agent', function () {
    $this->actingAs(User::factory()->create());
    $agent = Agent::factory()->create(['status' => 'active']);

    $this->post(route('agents.revoke', $agent))->assertRedirect();

    expect($agent->fresh()->status)->toBe('revoked');
});

it('redirects guests attempting to revoke an agent', function () {
    $agent = Agent::factory()->create();

    $this->post(route('agents.revoke', $agent))->assertRedirect(route('login'));
});

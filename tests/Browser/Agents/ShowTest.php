<?php

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    $agent = Agent::factory()->create();

    visit("/agents/{$agent->id}")
        ->assertPathIs('/login');
});

it('shows the agent details page', function () {
    $user = User::factory()->create();
    $agent = Agent::factory()->create(['name' => 'Production Server']);

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit("/agents/{$agent->id}")
        ->assertPathIs("/agents/{$agent->id}")
        ->assertSee('Production Server');
});

it('shows agent timeline on the detail page', function () {
    $user = User::factory()->create();
    $agent = Agent::factory()->create(['name' => 'Web Server']);

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit("/agents/{$agent->id}")
        ->assertSee('Web Server');
});

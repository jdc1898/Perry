<?php

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    visit('/agents')
        ->assertPathIs('/login');
});

it('shows the agents list page', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/agents')
        ->assertPathIs('/agents')
        ->assertSee('Agents');
});

it('lists registered agents', function () {
    $user = User::factory()->create();
    Agent::factory()->create(['name' => 'My Test Server']);

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/agents')
        ->assertSee('My Test Server');
});

it('shows a link to register a new agent', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/agents')
        ->assertSee('Register');
});

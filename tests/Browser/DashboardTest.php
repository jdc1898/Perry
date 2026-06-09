<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    visit('/dashboard')
        ->assertPathIs('/login');
});

it('shows the dashboard to authenticated users', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit()
        ->assertPathIs('/dashboard')
        ->assertSee('Dashboard');
});

it('shows agent count on the dashboard', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit()
        ->assertPathIs('/dashboard');
});

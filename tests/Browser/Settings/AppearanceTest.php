<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    visit('/settings/appearance')
        ->assertPathIs('/login');
});

it('shows the appearance settings page', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/settings/appearance')
        ->assertPathIs('/settings/appearance')
        ->assertSee('Appearance');
});

it('shows light and dark mode options', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/settings/appearance')
        ->assertSee('Light');
});

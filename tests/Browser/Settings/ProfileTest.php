<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    visit('/settings/profile')
        ->assertPathIs('/login');
});

it('shows the profile settings page', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/settings/profile')
        ->assertPathIs('/settings/profile')
        ->assertSee('Profile');
});

it('shows the user name on the profile page', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/settings/profile')
        ->assertSee('Jane Doe');
});

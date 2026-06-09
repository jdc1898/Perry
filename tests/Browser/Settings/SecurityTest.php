<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    visit('/settings/security')
        ->assertPathIs('/login');
});

it('shows the security settings page after confirming password', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    // Security page requires password confirmation — confirm first
    visit('/confirm-password')
        ->type('password', 'password')
        ->submit();

    visit('/settings/security')
        ->assertPathIs('/settings/security')
        ->assertSee('Security');
});

<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the two factor challenge page for 2fa users', function () {
    $user = User::factory()->withTwoFactor()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit()
        ->assertPathIs('/two-factor-challenge')
        ->assertSee('Authentication code');
});

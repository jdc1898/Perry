<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

it('shows the confirm password page', function () {
    $user = User::factory()->create();

    // Log in first, then visit the security page which requires password confirmation
    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit()
        ->assertPathIs('/dashboard');

    visit('/confirm-password')
        ->assertPathIs('/confirm-password')
        ->assertSee('Confirm password');
});

it('shows a password field', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/confirm-password')
        ->assertSee('Password');
});

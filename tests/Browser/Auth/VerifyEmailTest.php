<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the email verification page for unverified users', function () {
    $user = User::factory()->unverified()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit()
        ->assertPathIs('/verify-email')
        ->assertSee('verification');
});

it('shows resend verification email button', function () {
    $user = User::factory()->unverified()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit()
        ->assertSee('Resend verification email');
});

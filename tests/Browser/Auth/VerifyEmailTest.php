<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the email verification page when visited directly', function () {
    $user = User::factory()->unverified()->create();

    // Log in first (MustVerifyEmail is not enforced, so login succeeds normally)
    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit()
        ->assertPathIs('/dashboard');

    // Visit the email verification notice page directly
    visit('/email/verify')
        ->assertPathIs('/email/verify')
        ->assertSee('verification');
});

it('shows resend verification email button', function () {
    $user = User::factory()->unverified()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/email/verify')
        ->assertSee('Resend verification email');
});

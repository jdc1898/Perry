<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the confirm password page', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/user/confirm-password')
        ->assertPathIs('/user/confirm-password')
        ->assertSee('Confirm password');
});

it('shows a password field', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/user/confirm-password')
        ->assertSee('Password');
});

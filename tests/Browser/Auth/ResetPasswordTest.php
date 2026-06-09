<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('shows the reset password page with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    visit("/reset-password/{$token}?email={$user->email}")
        ->assertPathBeginsWith('/reset-password')
        ->assertSee('Reset password');
});

it('shows password and confirm password fields', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    visit("/reset-password/{$token}?email={$user->email}")
        ->assertSee('Password');
});

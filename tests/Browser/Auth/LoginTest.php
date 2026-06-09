<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the login page', function () {
    visit('/login')
        ->assertPathIs('/login')
        ->assertSee('Log in');
});

it('shows email and password fields', function () {
    visit('/login')
        ->assertSee('Email address')
        ->assertSee('Password');
});

it('redirects to dashboard after successful login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    visit('/login')
        ->type('email', 'test@example.com')
        ->type('password', 'password')
        ->submit()
        ->assertPathIs('/dashboard');
});

it('shows an error for invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    visit('/login')
        ->type('email', 'test@example.com')
        ->type('password', 'wrongpassword')
        ->submit()
        ->assertPathIs('/login')
        ->assertSee('credentials');
});

it('shows a link to forgot password', function () {
    visit('/login')
        ->assertSee('Forgot your password?');
});

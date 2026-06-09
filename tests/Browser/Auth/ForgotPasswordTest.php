<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the forgot password page', function () {
    visit('/forgot-password')
        ->assertPathIs('/forgot-password')
        ->assertSee('Forgot password');
});

it('shows an email field', function () {
    visit('/forgot-password')
        ->assertSee('Email address');
});

it('sends a password reset link', function () {
    visit('/forgot-password')
        ->type('email', 'test@example.com')
        ->submit()
        ->assertSee('sent');
});

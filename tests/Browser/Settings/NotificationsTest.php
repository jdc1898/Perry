<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    visit('/settings/notifications')
        ->assertPathIs('/login');
});

it('shows the notifications settings page', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/settings/notifications')
        ->assertPathIs('/settings/notifications')
        ->assertSee('Notification');
});

it('shows mail, slack and webhook channel sections', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/settings/notifications')
        ->assertSee('Mail')
        ->assertSee('Slack')
        ->assertSee('Webhook');
});

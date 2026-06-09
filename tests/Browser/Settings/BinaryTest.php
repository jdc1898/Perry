<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    visit('/settings/binary')
        ->assertPathIs('/login');
});

it('shows the binary settings page', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/settings/binary')
        ->assertPathIs('/settings/binary')
        ->assertSee('Agent binary');
});

it('shows an upload button on the binary page', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/settings/binary')
        ->assertSee('Upload');
});

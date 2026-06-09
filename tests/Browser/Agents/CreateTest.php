<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    visit('/agents/create')
        ->assertPathIs('/login');
});

it('shows the register agent form', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/agents/create')
        ->assertPathIs('/agents/create')
        ->assertSee('Register Agent');
});

it('shows a name field on the create form', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->submit();

    visit('/agents/create')
        ->assertSee('Name');
});

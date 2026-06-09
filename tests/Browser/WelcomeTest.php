<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the welcome page', function () {
    visit('/')
        ->assertPathIs('/')
        ->assertTitleContains('Perry');
});

it('shows a link to log in', function () {
    visit('/')
        ->assertSee('Log in');
});

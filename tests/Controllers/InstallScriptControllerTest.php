<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the install script as plain text', function () {
    $response = $this->get(route('install-script'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
});

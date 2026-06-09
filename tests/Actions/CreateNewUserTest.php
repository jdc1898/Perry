<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates a new user with valid input', function () {
    $user = (new CreateNewUser)->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Jane Doe')
        ->and($user->email)->toBe('jane@example.com');

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
});

it('throws a validation exception when required fields are missing', function () {
    (new CreateNewUser)->create([]);
})->throws(ValidationException::class);

it('throws a validation exception when passwords do not match', function () {
    (new CreateNewUser)->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]);
})->throws(ValidationException::class);

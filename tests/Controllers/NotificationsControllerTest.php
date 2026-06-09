<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from the notifications settings page', function () {
    $this->get(route('notifications.edit'))->assertRedirect(route('login'));
});

it('shows the notifications settings page to authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('notifications.edit'))
        ->assertOk();
});

it('updates notification channel settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->put(route('notifications.update'), [
        'mail' => ['enabled' => true,  'config' => ['addresses' => ['test@example.com']]],
        'slack' => ['enabled' => false, 'config' => ['webhook_url' => '']],
        'webhook' => ['enabled' => false, 'config' => ['url' => '', 'secret' => '']],
    ])->assertRedirect(route('notifications.edit'));

    $this->assertDatabaseHas('notification_channels', [
        'user_id' => $user->id,
        'type' => 'mail',
        'enabled' => true,
    ]);
});

it('shows existing channel config when channels are already configured', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Pre-create all three channels so the edit view hits the truthy config branch
    $this->put(route('notifications.update'), [
        'mail' => ['enabled' => true,  'config' => ['addresses' => ['a@example.com']]],
        'slack' => ['enabled' => true,  'config' => ['webhook_url' => 'https://hooks.slack.com/x']],
        'webhook' => ['enabled' => true,  'config' => ['url' => 'https://example.com/hook', 'secret' => 'abc']],
    ]);

    $this->get(route('notifications.edit'))->assertOk();
});

it('updates existing notification channels', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create initial channel
    $this->put(route('notifications.update'), [
        'mail' => ['enabled' => false, 'config' => ['addresses' => []]],
        'slack' => ['enabled' => false, 'config' => ['webhook_url' => '']],
        'webhook' => ['enabled' => false, 'config' => ['url' => '', 'secret' => '']],
    ]);

    // Update it
    $this->put(route('notifications.update'), [
        'mail' => ['enabled' => true, 'config' => ['addresses' => ['admin@example.com']]],
        'slack' => ['enabled' => false, 'config' => ['webhook_url' => '']],
        'webhook' => ['enabled' => false, 'config' => ['url' => '', 'secret' => '']],
    ])->assertRedirect(route('notifications.edit'));

    $this->assertDatabaseHas('notification_channels', [
        'user_id' => $user->id,
        'type' => 'mail',
        'enabled' => true,
    ]);
});

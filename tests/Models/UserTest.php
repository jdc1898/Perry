<?php

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Slack\SlackRoute;

uses(RefreshDatabase::class);

it('has a notificationChannels relationship', function () {
    $user = User::factory()->create();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'mail', 'enabled' => true]);
    expect($user->notificationChannels)->toHaveCount(1);
});

it('routeNotificationForMail returns configured addresses when mail channel is enabled', function () {
    $user = User::factory()->create();
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'mail',
        'enabled' => true,
        'config' => ['addresses' => ['alerts@example.com', 'ops@example.com']],
    ]);

    expect($user->routeNotificationForMail())->toBe(['alerts@example.com', 'ops@example.com']);
});

it('routeNotificationForMail falls back to user email when no mail channel exists', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    expect($user->routeNotificationForMail())->toBe(['user@example.com']);
});

it('routeNotificationForMail falls back to user email when mail channel is disabled', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'mail',
        'enabled' => false,
        'config' => ['addresses' => ['alerts@example.com']],
    ]);

    expect($user->routeNotificationForMail())->toBe(['user@example.com']);
});

it('routeNotificationForMail falls back to user email when channel config has no addresses', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'mail',
        'enabled' => true,
        'config' => ['addresses' => []],
    ]);

    expect($user->routeNotificationForMail())->toBe(['user@example.com']);
});

it('routeNotificationForSlack returns a SlackRoute with the webhook url', function () {
    $user = User::factory()->create();
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'slack',
        'enabled' => true,
        'config' => ['webhook_url' => 'https://hooks.slack.com/xxx'],
    ]);

    $route = $user->routeNotificationForSlack();
    expect($route)->toBeInstanceOf(SlackRoute::class);
});

it('routeNotificationForSlack returns an empty SlackRoute when no slack channel exists', function () {
    $user = User::factory()->create();

    $route = $user->routeNotificationForSlack();
    expect($route)->toBeInstanceOf(SlackRoute::class);
});

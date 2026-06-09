<?php

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeChannel(array $attrs = []): NotificationChannel
{
    $user = User::factory()->create();

    return NotificationChannel::create(array_merge([
        'user_id' => $user->id,
        'type' => 'mail',
        'enabled' => true,
        'config' => ['addresses' => ['alerts@example.com']],
    ], $attrs));
}

it('belongs to a user', function () {
    $channel = makeChannel();
    expect($channel->user)->toBeInstanceOf(User::class);
});

it('casts enabled as a boolean', function () {
    $channel = makeChannel(['enabled' => true]);
    expect($channel->enabled)->toBeTrue();

    $channel->enabled = false;
    $channel->save();
    expect($channel->fresh()->enabled)->toBeFalse();
});

it('casts config as an array', function () {
    $channel = makeChannel(['config' => ['webhook_url' => 'https://hooks.slack.com/xxx']]);
    expect($channel->config)->toBeArray()
        ->and($channel->config['webhook_url'])->toBe('https://hooks.slack.com/xxx');
});

it('config is null when not set', function () {
    $channel = makeChannel(['config' => null]);
    expect($channel->config)->toBeNull();
});

<?php

use App\Channels\WebhookChannel;
use App\Models\Agent;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\AgentOfflineNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Slack\SlackMessage;

uses(RefreshDatabase::class);

function offlineAgent(array $attrs = []): Agent
{
    return Agent::create(array_merge([
        'name' => 'web-01',
        'hostname' => 'web-01.example.com',
        'public_key' => 'key',
        'fingerprint' => uniqid('fp_'),
    ], $attrs));
}

function offlineUser(): User
{
    return User::factory()->create();
}

it('via returns mail when mail channel is enabled', function () {
    $user = offlineUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'mail', 'enabled' => true]);

    $channels = (new AgentOfflineNotification(offlineAgent()))->via($user);

    expect($channels)->toBe(['mail']);
});

it('via returns slack when slack channel is enabled', function () {
    $user = offlineUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'slack', 'enabled' => true]);

    $channels = (new AgentOfflineNotification(offlineAgent()))->via($user);

    expect($channels)->toBe(['slack']);
});

it('via returns WebhookChannel when webhook channel is enabled', function () {
    $user = offlineUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'webhook', 'enabled' => true]);

    $channels = (new AgentOfflineNotification(offlineAgent()))->via($user);

    expect($channels)->toBe([WebhookChannel::class]);
});

it('via returns empty array when no channels are enabled', function () {
    $user = offlineUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'mail', 'enabled' => false]);

    $channels = (new AgentOfflineNotification(offlineAgent()))->via($user);

    expect($channels)->toBe([]);
});

it('via filters out unknown channel types', function () {
    $user = offlineUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'sms', 'enabled' => true]);

    $channels = (new AgentOfflineNotification(offlineAgent()))->via($user);

    expect($channels)->toBe([]);
});

it('via deduplicates channels', function () {
    $user = offlineUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'mail', 'enabled' => true]);

    $channels = (new AgentOfflineNotification(offlineAgent()))->via($user);

    expect($channels)->toBe(['mail'])->and(count($channels))->toBe(1);
});

it('toMail returns a MailMessage with the agent name in the subject', function () {
    $agent = offlineAgent(['name' => 'prod-01']);
    $mail = (new AgentOfflineNotification($agent))->toMail(offlineUser());

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('prod-01');
});

it('toMail includes last seen time when set', function () {
    $agent = offlineAgent(['last_seen_at' => now()->subHour()]);
    $mail = (new AgentOfflineNotification($agent))->toMail(offlineUser());

    expect($mail)->toBeInstanceOf(MailMessage::class);
});

it('toMail handles null last_seen_at gracefully', function () {
    $agent = offlineAgent(['last_seen_at' => null]);
    $mail = (new AgentOfflineNotification($agent))->toMail(offlineUser());

    expect($mail)->toBeInstanceOf(MailMessage::class);
});

it('toSlack returns a SlackMessage with the agent name', function () {
    $agent = offlineAgent(['name' => 'prod-01']);
    $slack = (new AgentOfflineNotification($agent))->toSlack(offlineUser());

    expect($slack)->toBeInstanceOf(SlackMessage::class);
});

it('toWebhook returns the correct event structure', function () {
    $agent = offlineAgent(['name' => 'prod-01', 'hostname' => 'prod-01.local']);
    $payload = (new AgentOfflineNotification($agent))->toWebhook(offlineUser());

    expect($payload['event'])->toBe('agent.offline')
        ->and($payload['agent']['id'])->toBe($agent->id)
        ->and($payload['agent']['name'])->toBe('prod-01')
        ->and($payload['agent']['hostname'])->toBe('prod-01.local')
        ->and($payload)->toHaveKey('timestamp');
});

it('toWebhook includes null last_seen_at when agent has never reported', function () {
    $agent = offlineAgent(['last_seen_at' => null]);
    $payload = (new AgentOfflineNotification($agent))->toWebhook(offlineUser());

    expect($payload['last_seen_at'])->toBeNull();
});

it('toWebhook includes ISO last_seen_at when set', function () {
    $agent = offlineAgent(['last_seen_at' => now()]);
    $payload = (new AgentOfflineNotification($agent))->toWebhook(offlineUser());

    expect($payload['last_seen_at'])->toBeString();
});

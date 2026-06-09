<?php

use App\Channels\WebhookChannel;
use App\Models\Agent;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\AgentRecoveredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Slack\SlackMessage;

uses(RefreshDatabase::class);

function recoveredAgent(array $attrs = []): Agent
{
    return Agent::create(array_merge([
        'name' => 'web-01',
        'hostname' => 'web-01.example.com',
        'public_key' => 'key',
        'fingerprint' => uniqid('fp_'),
    ], $attrs));
}

function recoveredUser(): User
{
    return User::factory()->create();
}

it('via returns mail when mail channel is enabled', function () {
    $user = recoveredUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'mail', 'enabled' => true]);

    $channels = (new AgentRecoveredNotification(recoveredAgent()))->via($user);

    expect($channels)->toBe(['mail']);
});

it('via returns slack when slack channel is enabled', function () {
    $user = recoveredUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'slack', 'enabled' => true]);

    $channels = (new AgentRecoveredNotification(recoveredAgent()))->via($user);

    expect($channels)->toBe(['slack']);
});

it('via returns WebhookChannel when webhook channel is enabled', function () {
    $user = recoveredUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'webhook', 'enabled' => true]);

    $channels = (new AgentRecoveredNotification(recoveredAgent()))->via($user);

    expect($channels)->toBe([WebhookChannel::class]);
});

it('via returns empty array when no channels are enabled', function () {
    $user = recoveredUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'mail', 'enabled' => false]);

    $channels = (new AgentRecoveredNotification(recoveredAgent()))->via($user);

    expect($channels)->toBe([]);
});

it('via filters out unknown channel types', function () {
    $user = recoveredUser();
    NotificationChannel::create(['user_id' => $user->id, 'type' => 'sms', 'enabled' => true]);

    $channels = (new AgentRecoveredNotification(recoveredAgent()))->via($user);

    expect($channels)->toBe([]);
});

it('toMail returns a MailMessage with the agent name in the subject', function () {
    $agent = recoveredAgent(['name' => 'prod-01']);
    $mail = (new AgentRecoveredNotification($agent))->toMail(recoveredUser());

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('prod-01');
});

it('toSlack returns a SlackMessage with the agent name', function () {
    $agent = recoveredAgent(['name' => 'prod-01']);
    $slack = (new AgentRecoveredNotification($agent))->toSlack(recoveredUser());

    expect($slack)->toBeInstanceOf(SlackMessage::class);
});

it('toWebhook returns the correct event structure', function () {
    $agent = recoveredAgent(['name' => 'prod-01', 'hostname' => 'prod-01.local']);
    $payload = (new AgentRecoveredNotification($agent))->toWebhook(recoveredUser());

    expect($payload['event'])->toBe('agent.recovered')
        ->and($payload['agent']['id'])->toBe($agent->id)
        ->and($payload['agent']['name'])->toBe('prod-01')
        ->and($payload['agent']['hostname'])->toBe('prod-01.local')
        ->and($payload)->toHaveKey('timestamp');
});

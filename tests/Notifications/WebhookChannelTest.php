<?php

use App\Channels\WebhookChannel;
use App\Models\Agent;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\AgentOfflineNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

function webhookAgent(): Agent
{
    return Agent::create([
        'name' => 'web-01',
        'hostname' => 'web-01.example.com',
        'public_key' => 'key',
        'fingerprint' => uniqid('fp_'),
    ]);
}

function webhookUser(): User
{
    return User::factory()->create();
}

it('sends an HTTP POST to the webhook URL', function () {
    Http::fake();

    $user = webhookUser();
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'webhook',
        'enabled' => true,
        'config' => ['url' => 'https://example.com/hook'],
    ]);

    (new WebhookChannel)->send($user, new AgentOfflineNotification(webhookAgent()));

    Http::assertSent(fn ($request) => $request->url() === 'https://example.com/hook');
});

it('does nothing when no webhook channel exists', function () {
    Http::fake();

    $user = webhookUser();

    (new WebhookChannel)->send($user, new AgentOfflineNotification(webhookAgent()));

    Http::assertNothingSent();
});

it('does nothing when webhook channel is disabled', function () {
    Http::fake();

    $user = webhookUser();
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'webhook',
        'enabled' => false,
        'config' => ['url' => 'https://example.com/hook'],
    ]);

    (new WebhookChannel)->send($user, new AgentOfflineNotification(webhookAgent()));

    Http::assertNothingSent();
});

it('does nothing when webhook URL is empty', function () {
    Http::fake();

    $user = webhookUser();
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'webhook',
        'enabled' => true,
        'config' => ['url' => ''],
    ]);

    (new WebhookChannel)->send($user, new AgentOfflineNotification(webhookAgent()));

    Http::assertNothingSent();
});

it('includes HMAC signature header when secret is configured', function () {
    Http::fake();

    $user = webhookUser();
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'webhook',
        'enabled' => true,
        'config' => ['url' => 'https://example.com/hook', 'secret' => 'my-secret'],
    ]);

    (new WebhookChannel)->send($user, new AgentOfflineNotification(webhookAgent()));

    Http::assertSent(fn ($request) => str_starts_with($request->header('X-Perry-Signature')[0], 'sha256='));
});

it('does not include signature header when no secret is configured', function () {
    Http::fake();

    $user = webhookUser();
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'webhook',
        'enabled' => true,
        'config' => ['url' => 'https://example.com/hook'],
    ]);

    (new WebhookChannel)->send($user, new AgentOfflineNotification(webhookAgent()));

    Http::assertSent(fn ($request) => empty($request->header('X-Perry-Signature')));
});

it('logs an error when the HTTP request fails', function () {
    Http::fake(['*' => fn () => throw new Exception('Connection refused')]);
    Log::spy();

    $user = webhookUser();
    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'webhook',
        'enabled' => true,
        'config' => ['url' => 'https://example.com/hook'],
    ]);

    (new WebhookChannel)->send($user, new AgentOfflineNotification(webhookAgent()));

    Log::shouldHaveReceived('error')->once();
});

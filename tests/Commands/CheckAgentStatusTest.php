<?php

use App\Models\Agent;
use App\Models\User;
use App\Notifications\AgentOfflineNotification;
use App\Notifications\AgentRecoveredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

// Creates a user with an enabled mail channel so via() returns ['mail']
// and Notification::fake() can record dispatched notifications.
function userWithMailChannel(): User
{
    $user = User::factory()->create();
    $user->notificationChannels()->create([
        'type' => 'mail',
        'enabled' => true,
        'config' => ['addresses' => ['admin@example.com']],
    ]);

    return $user;
}

// ── Early exit ────────────────────────────────────────────────────────────────

it('does nothing when no user exists', function () {
    Agent::factory()->create([
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(20),
    ]);

    $this->artisan('perry:check-agents')->assertSuccessful();

    Notification::assertNothingSent();
});

// ── Offline detection ─────────────────────────────────────────────────────────

it('sends an offline notification for an agent whose last_seen_at is stale', function () {
    $user = userWithMailChannel();
    $agent = Agent::factory()->create([
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(20),
        'alerted_offline_at' => null,
    ]);

    $this->artisan('perry:check-agents')->assertSuccessful();

    Notification::assertSentTo($user, AgentOfflineNotification::class);
    expect($agent->fresh()->alerted_offline_at)->not->toBeNull();
});

it('sends an offline notification for an agent that has never been seen', function () {
    $user = userWithMailChannel();
    $agent = Agent::factory()->create([
        'status' => 'active',
        'last_seen_at' => null,
        'alerted_offline_at' => null,
    ]);

    $this->artisan('perry:check-agents')->assertSuccessful();

    Notification::assertSentTo($user, AgentOfflineNotification::class);
    expect($agent->fresh()->alerted_offline_at)->not->toBeNull();
});

it('does not re-notify when the agent is already alerted as offline', function () {
    $user = userWithMailChannel();
    Agent::factory()->create([
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(20),
        'alerted_offline_at' => now()->subMinutes(5),
    ]);

    $this->artisan('perry:check-agents')->assertSuccessful();

    Notification::assertNotSentTo($user, AgentOfflineNotification::class);
});

it('re-alerts when an agent goes offline again after recovering', function () {
    // alerted_offline_at < last_seen_at: agent was seen after the alert (recovered),
    // but last_seen_at is now stale again — so it has gone offline a second time.
    $user = userWithMailChannel();
    $agent = Agent::factory()->create([
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(15),
        'alerted_offline_at' => now()->subMinutes(30),
    ]);

    $this->artisan('perry:check-agents')->assertSuccessful();

    Notification::assertSentTo($user, AgentOfflineNotification::class);
    expect($agent->fresh()->alerted_offline_at)->not->toBeNull();
});

// ── Recovery detection ────────────────────────────────────────────────────────

it('sends a recovery notification when an offline-alerted agent comes back online', function () {
    $user = userWithMailChannel();
    $agent = Agent::factory()->create([
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(2),
        'alerted_offline_at' => now()->subMinutes(30),
    ]);

    $this->artisan('perry:check-agents')->assertSuccessful();

    Notification::assertSentTo($user, AgentRecoveredNotification::class);
    expect($agent->fresh()->alerted_offline_at)->toBeNull();
});

it('does not send a recovery notification for an agent that was never alerted', function () {
    $user = userWithMailChannel();
    Agent::factory()->create([
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(2),
        'alerted_offline_at' => null,
    ]);

    $this->artisan('perry:check-agents')->assertSuccessful();

    Notification::assertNotSentTo($user, AgentRecoveredNotification::class);
});

it('does not send any notification for an online agent with no prior alert', function () {
    userWithMailChannel();
    Agent::factory()->create([
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(2),
        'alerted_offline_at' => null,
    ]);

    $this->artisan('perry:check-agents')->assertSuccessful();

    Notification::assertNothingSent();
});

it('outputs the agent name when notifying offline', function () {
    userWithMailChannel();
    Agent::factory()->create([
        'name' => 'web-01',
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(20),
        'alerted_offline_at' => null,
    ]);

    $this->artisan('perry:check-agents')
        ->expectsOutputToContain('web-01 offline')
        ->assertSuccessful();
});

it('outputs the agent name when notifying recovery', function () {
    userWithMailChannel();
    Agent::factory()->create([
        'name' => 'web-01',
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(2),
        'alerted_offline_at' => now()->subMinutes(30),
    ]);

    $this->artisan('perry:check-agents')
        ->expectsOutputToContain('web-01 recovered')
        ->assertSuccessful();
});

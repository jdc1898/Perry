<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\User;
use App\Notifications\AgentOfflineNotification;
use App\Notifications\AgentRecoveredNotification;
use Illuminate\Console\Command;

class CheckAgentStatus extends Command
{
    protected $signature = 'perry:check-agents';

    protected $description = 'Send notifications for agents that go offline or recover';

    public function handle(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        // Agents that have gone offline and not yet alerted
        $wentOffline = Agent::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subMinutes(10));
            })
            ->where(function ($q) {
                $q->whereNull('alerted_offline_at')
                    ->orWhereColumn('alerted_offline_at', '<', 'last_seen_at');
            })
            ->get();

        foreach ($wentOffline as $agent) {
            $user->notify(new AgentOfflineNotification($agent));
            $agent->update(['alerted_offline_at' => now()]);
            $this->line("Notified: {$agent->name} offline");
        }

        // Agents that have recovered
        $recovered = Agent::where('status', 'active')
            ->whereNotNull('alerted_offline_at')
            ->where('last_seen_at', '>', now()->subMinutes(10))
            ->get();

        foreach ($recovered as $agent) {
            $user->notify(new AgentRecoveredNotification($agent));
            $agent->update(['alerted_offline_at' => null]);
            $this->line("Notified: {$agent->name} recovered");
        }
    }
}

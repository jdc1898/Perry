<?php

namespace App\Notifications;

use App\Channels\WebhookChannel;
use App\Models\Agent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;

class AgentOfflineNotification extends Notification
{
    public function __construct(public readonly Agent $agent) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        foreach ($notifiable->notificationChannels()->where('enabled', true)->get() as $channel) {
            $channels[] = match ($channel->type) {
                'mail'    => 'mail',
                'slack'   => 'slack',
                'webhook' => WebhookChannel::class,
                default   => null,
            };
        }
        return array_values(array_filter(array_unique($channels)));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🔴 Agent Offline: {$this->agent->name}")
            ->greeting("Agent Offline")
            ->line("**{$this->agent->name}** ({$this->agent->hostname}) has stopped reporting.")
            ->line('Last seen: ' . ($this->agent->last_seen_at?->diffForHumans() ?? 'Never'))
            ->action('View Agent', url("/agents/{$this->agent->id}"))
            ->line('You will receive another notification when the agent recovers.');
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text("🔴 *Agent Offline: {$this->agent->name}*")
            ->block(function (SectionBlock $block) {
                $block->text("*{$this->agent->name}* (`{$this->agent->hostname}`) has stopped reporting.\nLast seen: " . ($this->agent->last_seen_at?->diffForHumans() ?? 'Never'));
            });
    }

    public function toWebhook(object $notifiable): array
    {
        return [
            'event'       => 'agent.offline',
            'agent'       => [
                'id'       => $this->agent->id,
                'name'     => $this->agent->name,
                'hostname' => $this->agent->hostname,
            ],
            'last_seen_at' => $this->agent->last_seen_at?->toISOString(),
            'timestamp'    => now()->toISOString(),
        ];
    }
}

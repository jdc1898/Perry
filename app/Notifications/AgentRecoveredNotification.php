<?php

namespace App\Notifications;

use App\Channels\WebhookChannel;
use App\Models\Agent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;

class AgentRecoveredNotification extends Notification
{
    public function __construct(public readonly Agent $agent) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        foreach ($notifiable->notificationChannels()->where('enabled', true)->get() as $channel) {
            $channels[] = match ($channel->type) {
                'mail' => 'mail',
                'slack' => 'slack',
                'webhook' => WebhookChannel::class,
                default => null,
            };
        }

        return array_values(array_filter(array_unique($channels)));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("✅ Agent Recovered: {$this->agent->name}")
            ->greeting('Agent Recovered')
            ->line("**{$this->agent->name}** ({$this->agent->hostname}) is back online and reporting.")
            ->action('View Agent', url("/agents/{$this->agent->id}"));
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text("✅ *Agent Recovered: {$this->agent->name}*")
            ->sectionBlock(function (SectionBlock $block) {
                $block->text("*{$this->agent->name}* (`{$this->agent->hostname}`) is back online and reporting.");
            });
    }

    public function toWebhook(object $notifiable): array
    {
        return [
            'event' => 'agent.recovered',
            'agent' => [
                'id' => $this->agent->id,
                'name' => $this->agent->name,
                'hostname' => $this->agent->hostname,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}

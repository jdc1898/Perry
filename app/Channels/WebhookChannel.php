<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $channel = $notifiable->notificationChannels()
            ->where('type', 'webhook')
            ->where('enabled', true)
            ->first();

        if (! $channel || empty($channel->config['url'])) {
            return;
        }

        $payload = $notification->toWebhook($notifiable);
        $body = json_encode($payload);

        $headers = [];
        if (! empty($channel->config['secret'])) {
            $headers['X-Perry-Signature'] = 'sha256='.hash_hmac('sha256', $body, $channel->config['secret']);
        }

        try {
            Http::timeout(10)->withHeaders($headers)->post($channel->config['url'], $payload);
        } catch (\Throwable $e) {
            Log::error('Webhook notification failed', ['url' => $channel->config['url'], 'error' => $e->getMessage()]);
        }
    }
}

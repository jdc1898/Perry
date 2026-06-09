<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    public function edit(Request $request): Response
    {
        $channels = $request->user()->notificationChannels()->get()->keyBy('type');

        return Inertia::render('settings/Notifications', [
            'channels' => [
                'mail' => [
                    'enabled' => $channels->has('mail') ? $channels->get('mail')->enabled : false,
                    'config' => $channels->has('mail')
                        ? $channels->get('mail')->config
                        : ['addresses' => []],
                ],
                'slack' => [
                    'enabled' => $channels->has('slack') ? $channels->get('slack')->enabled : false,
                    'config' => $channels->has('slack')
                        ? $channels->get('slack')->config
                        : ['webhook_url' => ''],
                ],
                'webhook' => [
                    'enabled' => $channels->has('webhook') ? $channels->get('webhook')->enabled : false,
                    'config' => $channels->has('webhook')
                        ? $channels->get('webhook')->config
                        : ['url' => '', 'secret' => ''],
                ],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'mail.enabled' => ['boolean'],
            'mail.config.addresses' => ['array'],
            'mail.config.addresses.*' => ['email'],
            'slack.enabled' => ['boolean'],
            'slack.config.webhook_url' => ['nullable', 'url'],
            'webhook.enabled' => ['boolean'],
            'webhook.config.url' => ['nullable', 'url'],
            'webhook.config.secret' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        foreach (['mail', 'slack', 'webhook'] as $type) {
            $data = $request->input($type, []);
            $user->notificationChannels()->updateOrCreate(
                ['type' => $type],
                [
                    'enabled' => $data['enabled'] ?? false,
                    'config' => $data['config'] ?? [],
                ]
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Notification settings saved.']);

        return to_route('notifications.edit');
    }
}

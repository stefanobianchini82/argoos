<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $webhookUrl = $notifiable->routeNotificationFor('slack');

        if (blank($webhookUrl)) {
            Log::warning('SlackChannel: missing webhook URL.');
            return;
        }

        $payload = $notification->toSlack($notifiable);

        if (blank($payload)) {
            return;
        }

        $response = Http::post($webhookUrl, $payload);

        if (! $response->successful()) {
            Log::error('SlackChannel: failed to send message.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }
    }
}

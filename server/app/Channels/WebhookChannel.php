<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $url = $notifiable->routeNotificationFor('webhook');

        if (blank($url)) {
            Log::warning('WebhookChannel: no webhook URL configured.');
            return;
        }

        $payload = $notification->toWebhook($notifiable);

        if (blank($payload)) {
            return;
        }

        $response = Http::post($url, $payload);

        if (! $response->successful()) {
            Log::error('WebhookChannel: failed to deliver payload.', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }
    }
}

<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $chatId = $notifiable->routeNotificationFor('telegram');
        $token  = config('services.telegram.bot_token');

        if (blank($chatId) || blank($token)) {
            Log::warning('TelegramChannel: missing chat_id or bot_token.', [
                'chat_id' => $chatId,
            ]);
            return;
        }

        $text = $notification->toTelegram($notifiable);

        if (blank($text)) {
            return;
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]);

        if (! $response->successful()) {
            Log::error('TelegramChannel: failed to send message.', [
                'chat_id' => $chatId,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
        }
    }
}

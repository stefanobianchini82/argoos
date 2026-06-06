<?php

namespace App\Services;

use App\Models\HttpCheck;
use App\Models\HttpCheckEvent;
use App\Notifications\HttpCheckDown;
use App\Notifications\HttpCheckRecovered;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpChecker
{
    public function check(HttpCheck $httpCheck): void
    {
        [$isUp, $statusCode, $responseMs, $context] = $this->probe($httpCheck);

        $openEvent = $httpCheck->httpCheckEvents()
            ->whereNull('resolved_at')
            ->latest('triggered_at')
            ->first();

        if (! $isUp && $openEvent === null) {
            $event = HttpCheckEvent::create([
                'http_check_id' => $httpCheck->id,
                'is_up'         => false,
                'status_code'   => $statusCode,
                'response_ms'   => $responseMs,
                'triggered_at'  => now(),
                'context'       => $context ?: null,
            ]);

            $httpCheck->update(['last_notified_at' => now()]);

            $this->sendNotification($httpCheck, $event, 'down');
        } elseif ($isUp && $openEvent !== null) {
            $openEvent->update([
                'resolved_at' => now(),
                'is_up'       => true,
                'status_code' => $statusCode,
                'response_ms' => $responseMs,
            ]);

            $this->sendNotification($httpCheck, $openEvent, 'recovered');
        }
    }

    private function probe(HttpCheck $httpCheck): array
    {
        $start = microtime(true);
        try {
            $response = Http::timeout($httpCheck->timeout_seconds)
                ->withOptions(['verify' => true])
                ->{strtolower($httpCheck->method)}($httpCheck->url);

            $responseMs = (int) round((microtime(true) - $start) * 1000);
            $statusCode = $response->status();

            $isUp = ($statusCode === (int) $httpCheck->expected_status_code);

            if ($isUp && filled($httpCheck->keyword_match)) {
                $isUp = str_contains($response->body(), $httpCheck->keyword_match);
            }

            $context = [];
            if (! $isUp) {
                $context = filled($httpCheck->keyword_match) && $statusCode === (int) $httpCheck->expected_status_code
                    ? ['reason' => 'keyword_not_found', 'keyword' => $httpCheck->keyword_match]
                    : ['reason' => 'unexpected_status', 'got_status' => $statusCode, 'expected' => $httpCheck->expected_status_code];
            }

            return [$isUp, $statusCode, $responseMs, $context];
        } catch (ConnectionException $e) {
            $responseMs = (int) round((microtime(true) - $start) * 1000);

            return [false, 0, $responseMs, ['reason' => 'connection_error', 'error' => $e->getMessage()]];
        } catch (\Throwable $e) {
            Log::error('HttpChecker: unexpected error probing endpoint.', [
                'check_id' => $httpCheck->id,
                'url'      => $httpCheck->url,
                'error'    => $e->getMessage(),
            ]);

            return [false, 0, null, ['reason' => 'unexpected_error', 'error' => $e->getMessage()]];
        }
    }

    private function sendNotification(HttpCheck $check, HttpCheckEvent $event, string $type): void
    {
        $notification = $type === 'down'
            ? new HttpCheckDown($check, $event)
            : new HttpCheckRecovered($check, $event);

        $notifiable = new AnonymousNotifiable;

        $notifiable = match ($check->channel) {
            'telegram' => $notifiable->route('telegram', $check->channel_target),
            'slack'    => $notifiable->route('slack', $check->channel_target),
            'webhook'  => $notifiable->route('webhook', $check->channel_target),
            default    => $notifiable->route('mail', $check->channel_target),
        };

        if (blank($check->channel_target)) {
            Log::warning('HttpChecker: no channel_target configured.', ['check_id' => $check->id]);
            return;
        }

        $notifiable->notify($notification);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['key', 'value'];

    public const ALERT_EMAIL                  = 'alert_email';
    public const TELEGRAM_CHAT_ID             = 'telegram_chat_id';
    public const SLACK_WEBHOOK_URL             = 'slack_webhook_url';
    public const HOST_OFFLINE_EMAIL_ENABLED    = 'host_offline.email_enabled';
    public const HOST_OFFLINE_TELEGRAM_ENABLED = 'host_offline.telegram_enabled';
    public const HOST_OFFLINE_SLACK_ENABLED    = 'host_offline.slack_enabled';

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::find($key);

        return $row !== null ? $row->value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value === '' ? null : (string) $value],
        );
    }
}

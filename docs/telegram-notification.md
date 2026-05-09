# Telegram Notifications — Setup Guide

This guide walks through creating a Telegram bot and obtaining the credentials needed to configure Argoos alert notifications.

---

## Prerequisites

- A Telegram account
- The Telegram app (mobile or desktop)

---

## Step 1 — Create a Bot via BotFather

1. Open Telegram and search for **@BotFather** (official, verified account with blue checkmark).
2. Start a conversation: tap **Start** or send `/start`.
3. Send the command:
   ```
   /newbot
   ```
4. BotFather will ask for a **name** for your bot (display name, e.g. `Argoos Alerts`).
5. Then it will ask for a **username** — must end in `bot` (e.g. `argoos_alerts_bot`).
6. On success, BotFather replies with your **bot token**:
   ```
   Done! Congratulations on your new bot. You will find it at t.me/argoos_alerts_bot.
   Use this token to access the HTTP API:
   7412345678:AAFxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```

**Save this token** — you will paste it into Argoos settings.

---

## Step 2 — Obtain Your Chat ID

The bot needs to know where to send messages. The chat ID identifies your personal chat (or a group/channel).

### Option A — Personal chat (simplest)

1. Search for your bot by username (e.g. `@argoos_alerts_bot`) and open it.
2. Send any message (e.g. `/start` or `hello`).
3. Open a browser and call the Telegram API to retrieve updates:
   ```
   https://api.telegram.org/bot<YOUR_TOKEN>/getUpdates
   ```
   Replace `<YOUR_TOKEN>` with the token from Step 1.
4. The response is JSON. Look for the `chat.id` field inside `message`:
   ```json
   {
     "result": [
       {
         "message": {
           "chat": {
             "id": 123456789,
             "type": "private"
           }
         }
       }
     ]
   }
   ```
5. The number under `chat.id` is your **Chat ID** (may be negative for groups).

### Option B — Group chat

1. Create a Telegram group or open an existing one.
2. Add your bot to the group (search by username → **Add to Group**).
3. Send a message in the group (e.g. `/start@argoos_alerts_bot`).
4. Call the same `getUpdates` URL above — look for a result where `chat.type` is `"group"` or `"supergroup"`. The `chat.id` will be a **negative number** (e.g. `-1001234567890`).

> **Tip**: If `getUpdates` returns an empty `result` array, send another message in the chat and try again.

---

## Step 3 — Configure Argoos

1. Log in to the Argoos dashboard.
2. Navigate to **Settings**.
3. Fill in:
   - **Telegram Bot Token**: the token from BotFather (e.g. `7412345678:AAFxxx...`)
   - **Telegram Chat ID**: the chat ID from Step 2 (e.g. `123456789` or `-1001234567890`)
4. Save settings.

---

## Step 4 — Enable Telegram on Alert Rules

1. Go to **Alerts** in the dashboard.
2. Create or edit an alert rule.
3. Under **Notification Channels**, enable **Telegram**.
4. Save the rule.

Argoos will now send a Telegram message whenever the alert is triggered or resolved.

---

## Troubleshooting

| Problem | Cause | Fix |
|---|---|---|
| `getUpdates` returns empty result | Bot has not received any messages | Send a message to the bot first |
| Notifications not delivered | Wrong Chat ID | Re-check the `chat.id` value; groups use negative IDs |
| `401 Unauthorized` from Telegram API | Invalid token | Re-copy the token from BotFather (no trailing spaces) |
| Bot cannot send to group | Bot was not added or is not an admin | Add the bot to the group and grant it **Send Messages** permission |

---

## Security Notes

- Keep your bot token private — anyone with the token can send messages as your bot.
- If the token is compromised, revoke it immediately via BotFather: `/revoke`.
- Argoos stores the token in the `settings` table; ensure your server database is not publicly accessible.

# Slack Notifications — Setup Guide

This guide walks through creating a Slack Incoming Webhook and configuring it in Argoos to receive alert notifications in a Slack channel.

---

## Prerequisites

- A Slack workspace where you have permission to install apps (typically **Admin** or **Owner** role, or a workspace that allows members to install apps).

---

## Step 1 — Create a Slack App

1. Go to [https://api.slack.com/apps](https://api.slack.com/apps) and sign in.
2. Click **Create New App**.
3. Choose **From scratch**.
4. Enter an **App Name** (e.g. `Argoos Alerts`) and select your **workspace**.
5. Click **Create App**.

---

## Step 2 — Enable Incoming Webhooks

1. In the left sidebar of your app settings, click **Incoming Webhooks**.
2. Toggle **Activate Incoming Webhooks** to **On**.
3. Scroll down and click **Add New Webhook to Workspace**.
4. Select the **channel** where Argoos alerts should be posted (e.g. `#alerts` or `#ops`).
5. Click **Allow**.
6. Slack generates a **Webhook URL** that looks like:
   ```
   https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX
   ```

**Copy this URL** — you will paste it into Argoos settings.

> The webhook URL is tied to a specific channel. To send to a different channel, add another webhook.

---

## Step 3 — Configure Argoos

1. Log in to the Argoos dashboard.
2. Navigate to **Settings**.
3. Paste the Webhook URL into the **Slack Webhook URL** field.
4. Save settings.

---

## Step 4 — Enable Slack on Alert Rules

1. Go to **Alerts** in the dashboard.
2. Create or edit an alert rule.
3. Under **Notification Channels**, enable **Slack**.
4. Save the rule.

Argoos will POST a message to your Slack channel whenever the alert is triggered or resolved.

---

## Customizing the Channel (Optional)

By default, the webhook posts to the channel selected during setup. You can override the destination at send time by including a `channel` field in the payload — but this requires the app to have the `chat:write` OAuth scope, which is not needed for basic webhook usage.

The simplest approach is to create one webhook per channel from the **Incoming Webhooks** page.

---

## Troubleshooting

| Problem | Cause | Fix |
|---|---|---|
| `404` or `invalid_payload` from Slack | Malformed webhook URL | Re-copy the full URL from the Slack app settings page |
| `channel_not_found` | Channel was deleted or webhook is stale | Create a new webhook pointing to an existing channel |
| No messages received | Wrong channel or webhook URL in Argoos | Check the Slack Webhook URL in Argoos Settings |
| `token_revoked` | App was uninstalled or webhook revoked | Re-create the app or re-add the webhook |
| Argoos shows success but no Slack message | Firewall blocking outbound HTTPS | Ensure the server can reach `hooks.slack.com` on port 443 |

---

## Security Notes

- The webhook URL acts as a secret — anyone with it can post to your channel.
- Do not commit the URL to version control.
- If the URL is compromised, delete it from the Slack app settings (**Incoming Webhooks** → revoke) and generate a new one.
- Argoos stores the URL in the `settings` table; ensure your server database is not publicly accessible.

---

## Revoking a Webhook

1. Go to [https://api.slack.com/apps](https://api.slack.com/apps) and open your app.
2. Click **Incoming Webhooks** in the sidebar.
3. Find the webhook and click the trash icon to revoke it.
4. Update the Argoos Settings with the new webhook URL if you generate a replacement.

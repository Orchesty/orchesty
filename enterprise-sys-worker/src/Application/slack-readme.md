# Slack Connector

An [Orchesty](https://orchesty.io) connector for Slack, a cloud-based messaging platform for team communication and collaboration.

## Application Type

**OAuth 2.0**

This connector uses the Slack OAuth 2.0 authorization flow (`https://slack.com/oauth/v2/authorize`). After entering your credentials in Orchesty, you will be redirected to Slack to authorize access.

| Field | Description |
|---|---|
| `client_id` | Your Slack app Client ID |
| `client_secret` | Your Slack app Client Secret |

## Components

| Class | Type | Description |
|---|---|---|
| `SlackSendMessageConnector` | Connector | Sends a message with an mrkdwn block to a specified channel via `POST https://slack.com/api/chat.postMessage` |

## Setup

### Credentials

1. Log in to [Slack API](https://api.slack.com/apps) and create a new app (from scratch or from a manifest).
2. Navigate to **OAuth & Permissions** and add the required Bot Token Scopes: `app_mentions:read`, `chat:write`, `chat:write.public`.
3. Under **Basic Information**, copy the **Client ID** and **Client Secret**.
4. Add the Orchesty redirect URL to the **Redirect URLs** list.
5. In Orchesty, open the Slack application settings, enter both values, and complete the OAuth authorization flow.

### API Documentation

Slack API: [https://api.slack.com/](https://api.slack.com/)

## How It Works

The `SlackSendMessageConnector` sends a message to a Slack channel using the [chat.postMessage](https://api.slack.com/methods/chat.postMessage) API method. It constructs a [Block Kit](https://api.slack.com/block-kit) message with an `mrkdwn` section block.

Authentication is handled automatically via the OAuth 2.0 access token stored in the application install.

### Input payload

The connector expects a JSON object with a `channel` field specifying the target Slack channel ID or name:

```json
{
  "channel": "C01ABCDEF12"
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `channel` | string | Yes | Slack channel ID (e.g. `C01ABCDEF12`) or channel name (e.g. `#general`) |

The message text is derived from the current Orchesty user context. The connector builds a Block Kit payload internally:

```json
{
  "channel": "C01ABCDEF12",
  "blocks": [
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "Hello, *username*"
      }
    }
  ]
}
```

### Output payload

The connector passes the original `ProcessDto` through after a successful send. The Slack API response (including `ts`, `channel`, `message`) is not currently mapped to the output.

### Finding Channel IDs

To find a channel ID in Slack:
1. Right-click the channel name in Slack
2. Select **View channel details**
3. At the bottom of the details panel, copy the **Channel ID**

Alternatively, use the [conversations.list](https://api.slack.com/methods/conversations.list) API method.

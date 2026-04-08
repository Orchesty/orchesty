---
title: Settings
helpId: settings/overview
order: 1
---

# Settings

The Settings page provides system configuration options organized into tabs.

## Workers

Workers are backend services that execute topology nodes and host applications. They provide the connectors, custom actions, and batch operations available in the Topology Designer.

### Worker table

| Column | Description |
|--------|-------------|
| **Name** | Display name of the worker. |
| **URL** | The endpoint URL where the worker is running. |
| **Headers** | Optional HTTP headers sent with requests to the worker (e.g., authentication tokens). |

### Managing workers

- **Add** -- click **+ Worker** in the top-right corner. Enter a name, URL, and optional headers.
- **Edit** -- click the edit icon on a row to modify the worker's configuration.
- **Delete** -- click the delete icon on a row to remove the worker.

## API Tokens

API tokens allow external systems to authenticate with the platform's API.

### Token table

| Column | Description |
|--------|-------------|
| **Name** | Descriptive name for the token. |
| **Created** | When the token was generated. |
| **Expiration** | Expiry date, or "No expiration" for permanent tokens. |
| **Scopes** | Permission scopes assigned to the token, shown as badges. |

### Creating a token

Click **+ Token** to open the creation dialog. Provide a name, select an expiration period, and choose one or more scopes that define what the token is allowed to access.

After generating the token, the token value is displayed **only once**. Copy it immediately -- it cannot be retrieved later.

### Deleting a token

Click the delete icon on a row. This action is permanent and any systems using the token will lose access.

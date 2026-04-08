---
title: Applications
helpId: applications/overview
order: 1
---

# Applications

The Applications page is a catalog of available integrations, organized by worker. Each card represents an application that can be installed and configured to work with topology connectors.

## Application lifecycle

Applications go through four states:

| State | Meaning |
|-------|---------|
| **Available** | The application is provided by a worker but has not been installed yet. |
| **Installed** | The application has been added but credentials and settings are not yet configured. |
| **Authorized** | Credentials are configured (API keys entered, OAuth flow completed). |
| **Activated** | The application is fully ready and can be used by connectors in topologies. |

Each step builds on the previous one. An application must be authorized before it can be activated.

## Filters

Use the radio buttons to filter applications by status: **All**, **Available**, **Installed**, **Unauthorized**, **Authorized**, **Activated**. The search field filters by application name.

## Application detail

Click a card to open the detail drawer. The drawer shows the application description, its current status, and the hosting worker name.

### Configuration tabs

Settings are organized into tabs generated from the application's configuration schema. Each tab has its own fields and can be **saved independently**.

For **OAuth applications**, the authorization tab includes a **Save & Authorize** button that redirects to the provider's authentication page (e.g., Google, GitHub). After completing the OAuth flow, you are returned to the application detail. If the application is already authorized, a **Re-authorize** button is available to refresh the credentials.

Some applications include built-in documentation accessible via the **(i)** icon in the drawer, which toggles between the settings form and the documentation view.

### Actions

The drawer header contains lifecycle actions depending on the current state:

- **Install** -- adds an available application.
- **Activate** / **Deactivate** -- enables or disables an authorized application.
- **Uninstall** -- removes the application and its configuration.

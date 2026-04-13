---
title: Applications
helpId: applications/overview
order: 1
---

# Applications

Applications are integrations that connect Orchesty to external services (CRMs, e-commerce platforms, databases, APIs, etc.). They provide connectors that you use as nodes inside topologies to send and receive data.

The Applications page shows all available integrations organized by worker. Each card represents one application.

## Application lifecycle

Applications go through four states. Each step builds on the previous one.

| State | What it means |
|-------|---------------|
| **Available** | The worker provides this application but it has not been installed yet. No configuration exists. |
| **Installed** | The application has been added to the instance. You can now fill in credentials and settings, but it is not yet usable in topologies. |
| **Authorized** | Credentials are configured (API keys entered or OAuth flow completed). The application can communicate with the external service, but it is not yet active. |
| **Activated** | The application is fully operational. Connectors in topologies can use it to process data. |

### Why authorization and activation are separate

Authorizing an application only stores credentials -- it does not start any background work. Activation is a deliberate step because it can trigger side effects: some applications register webhooks, start polling jobs, or perform other setup actions in the external service when activated. Keeping these steps separate lets you configure credentials safely without accidentally triggering those actions. Activation is most relevant when an application acts as a module that needs to set up external listeners (e.g., webhook subscriptions).

## Configuration

Settings are organized into tabs generated from the application's configuration schema. Each tab has its own fields and can be saved independently.

For **OAuth applications**, the authorization tab includes **Save & Authorize** which redirects to the provider's authentication page. If the application is already authorized, **Re-authorize** refreshes the credentials.

Some applications include built-in documentation accessible via the **(i)** icon, which toggles between the settings form and the documentation view.

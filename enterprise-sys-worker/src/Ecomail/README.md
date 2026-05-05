# Ecomail Integration

Orchesty integration for [Ecomail](https://ecomail.app/) — an email marketing platform for newsletters, automations, and transactional emails.

## Components

| Component | Type | Node name | Description |
|---|---|---|---|
| **EcomailApplication** | Application | `ecomail` | API key authentication + service settings |
| **EcomailSendTransactionalEmailConnector** | Connector | `ecomail-send-transactional-email` | Sends a transactional email using an Ecomail template |
| **EcomailSubscribeNewsletterConnector** | Connector | `ecomail-subscribe-newsletter` | Subscribes an email to the configured newsletter list with double opt-in |
| **EcomailSendSalesBusinessNotificationConnector** | Connector | `ecomail-send-sales-business-notification` | Sends a notification about a new sales lead to the configured business email (derived from `EcomailSendTransactionalEmailConnector`) |
| **EcomailSendSalesCustomerConfirmationConnector** | Connector | `ecomail-send-sales-customer-confirmation` | Sends a confirmation email to the customer after a sales form submission (derived from `EcomailSendTransactionalEmailConnector`) |

## Application Settings

### Authorization — API Key

1. Log in to your [Ecomail](https://ecomail.app/) account
2. Go to **Manage your account** → **For developers**
3. Click **Copy API Key**

### Settings

| Field | Required | Description |
|---|---|---|
| `newsletter_list_id` | yes | Numeric ID of the Ecomail list used by the newsletter signup form. Must be configured for **double opt-in** in the Ecomail UI (otherwise subscribers are added directly without confirmation). |
| `from_email` | yes | Sender email used by the sales transactional emails. Must be from a verified domain in Ecomail. |
| `from_name` | yes | Sender display name used by the sales transactional emails. |
| `business_email` | yes | Recipient address that receives internal notifications when a new sales lead is created. |
| `sales_business_template_id` | yes | Numeric ID of the Ecomail template used for the business notification email. |
| `sales_customer_template_id` | yes | Numeric ID of the Ecomail template used for the customer confirmation email. |

To find a list or template ID, open the list/template in Ecomail — the URL contains the ID, or it is shown in the overview.

## Connector: Send Transactional Email

Sends a transactional email using a pre-defined template in Ecomail (`POST /transactional/send-template`).

### Input Payload

```json
{
  "template_id": 42,
  "subject": "Your order has been shipped",
  "from_name": "My Store",
  "from_email": "orders@mystore.cz",
  "reply_to": "support@mystore.cz",
  "to": [
    {
      "email": "customer@example.com",
      "name": "Jan Novák"
    }
  ],
  "global_merge_vars": [
    { "name": "order_number", "content": "ORD-12345" },
    { "name": "tracking_url", "content": "https://tracking.example.com/abc" }
  ],
  "options": {
    "click_tracking": true,
    "open_tracking": true
  }
}
```

| Field | Required | Description |
|---|---|---|
| `template_id` | yes | ID of the Ecomail template |
| `subject` | yes | Email subject line |
| `from_name` | yes | Sender display name |
| `from_email` | yes | Sender email (must be from a verified domain in Ecomail) |
| `reply_to` | no | Reply-to address |
| `to` | yes | Array of recipients (`email`, optional `name`, `cc`, `bcc`) |
| `global_merge_vars` | no | Array of `{ name, content }` for template personalization |
| `attachments` | no | Array of `{ type, name, content }` where `content` is base64-encoded (4 MB message limit) |
| `options` | no | `click_tracking` and `open_tracking` booleans |

### Output Payload

```json
{
  "total_rejected_recipients": 0,
  "total_accepted_recipients": 1,
  "id": 11668787484950529
}
```

## Connector: Subscribe to Newsletter (Double Opt-In)

Subscribes an email address to the list configured in `newsletter_list_id` application setting (`POST /lists/{list_id}/subscribe`). Always sends `skip_confirmation: false`, so Ecomail dispatches the double opt-in confirmation email and the subscriber is added with status `6` (unconfirmed) until they click the link in that email.

> The Ecomail list itself must be configured for double opt-in (Lists → settings) — otherwise Ecomail confirms the subscriber automatically and no DOI email is sent.

### Input Payload

```json
{
  "email": "customer@example.com"
}
```

| Field | Required | Description |
|---|---|---|
| `email` | yes | Email address to subscribe |

### Output Payload

```json
{
  "id": 259471,
  "name": null,
  "surname": null,
  "email": "customer@example.com",
  "inserted_at": "2026-04-27 13:50:04",
  "already_subscribed": false
}
```

`already_subscribed: true` is returned when the address is already in the list — the connector treats this as a successful operation (no error).

## Connector: Send Sales Business Notification

Derived from `EcomailSendTransactionalEmailConnector`. Reads recipient (`business_email`), sender (`from_email`/`from_name`), and template (`sales_business_template_id`) from the Ecomail application settings. Builds a locale-aware subject (cs/en) and forwards the entire sales form payload as `global_merge_vars` for use in the Ecomail template.

Reads the sales form context from `dto` (set by upstream Pipedrive nodes), validates `firstName`, `lastName`, `personId`, `leadId`, sends the email, and writes back `businessEmailId` while preserving the rest of the context.

## Connector: Send Sales Customer Confirmation

Derived from `EcomailSendTransactionalEmailConnector`. Reads sender (`from_email`/`from_name`) and template (`sales_customer_template_id`) from the Ecomail application settings. Recipient is the customer's `email` from the form payload. Subject is locale-aware (cs/en). All sales form fields are sent as `global_merge_vars`.

Reads the sales form context from `dto`, validates `firstName`, `lastName`, `email`, sends the email, and writes back `customerEmailId` while preserving the rest of the context.

### Sales merge vars (template placeholders)

`buildSalesMergeVars` (`src/Sales/mergeVars.ts`) is shared by both sales connectors and produces a list of `{ name, content }` entries.

**Naming convention:** every name is **uppercased** and prefixed with `ORCH_`. Ecomail silently injects a number of default merge tags (e.g. `*|EMAIL|*`, `*|NAME|*`, `*|SURNAME|*`, …) that would otherwise collide with our payload — the prefix guarantees no overlap and makes our tags trivially recognizable inside the template.

In Ecomail templates reference them as `*|ORCH_…|*`. Copy-paste-ready table:

| Merge tag | Source | Notes |
|---|---|---|
| `*\|ORCH_FORM\|*` | `ctx.form` | Form identifier, e.g. `sales-inquiry` |
| `*\|ORCH_SUBMITTED_AT\|*` | `ctx.submittedAt` | ISO timestamp |
| `*\|ORCH_SOURCE\|*` | `ctx.source` | Origin URL / id |
| `*\|ORCH_LOCALE\|*` | `ctx.locale` | `cs` / `en` |
| `*\|ORCH_FIRST_NAME\|*` | `ctx.firstName` |  |
| `*\|ORCH_LAST_NAME\|*` | `ctx.lastName` |  |
| `*\|ORCH_FULL_NAME\|*` | `firstName + lastName` | precomputed |
| `*\|ORCH_EMAIL\|*` | `ctx.email` |  |
| `*\|ORCH_PHONE\|*` | `ctx.phone` | empty string if not provided |
| `*\|ORCH_COMPANY\|*` | `ctx.company` |  |
| `*\|ORCH_JOB_TITLE\|*` | `ctx.jobTitle` | empty string if not provided |
| `*\|ORCH_COMPANY_SIZE\|*` | `ctx.companySize` | empty string if not provided |
| `*\|ORCH_MESSAGE\|*` | `ctx.message` | free-form text |
| `*\|ORCH_ORG_ID\|*` | `ctx.orgId` | Pipedrive Organization id |
| `*\|ORCH_PERSON_ID\|*` | `ctx.personId` | Pipedrive Person id |
| `*\|ORCH_LEAD_ID\|*` | `ctx.leadId` | Pipedrive Lead UUID |
| `*\|ORCH_LEAD_URL\|*` | `ctx.leadUrl` | Deep link `https://{subdomain}.pipedrive.com/leads/inbox/{leadId}` |
| `*\|ORCH_NOTE_ID\|*` | `ctx.noteId` | Pipedrive Note id |
| `*\|ORCH_IP\|*` | `ctx.meta.ip` | technical meta |
| `*\|ORCH_USER_AGENT\|*` | `ctx.meta.userAgent` | technical meta |

## Topologies

| File | Trigger | Description |
|---|---|---|
| `src/topologies/newsletter-subscribe.tplg.json` | Start | Newsletter signup form posts `{ email }` → DOI subscribe in Ecomail |
| `src/topologies/sales-form.tplg.json` | Start | Sales form posts the full inquiry payload → Pipedrive (Organization, Person, Lead, Note) → Ecomail business notification + customer confirmation |

## API Reference

- Base URL: `https://api2.ecomailapp.cz`
- Auth: API key in `key` header
- Docs: [https://docs.ecomail.cz/](https://docs.ecomail.cz/)
- Transactional templates: [https://docs.ecomail.cz/api-reference/transactional/send-template](https://docs.ecomail.cz/api-reference/transactional/send-template)
- Subscribe to list: [https://docs.ecomail.cz/api-reference/lists/subscribe](https://docs.ecomail.cz/api-reference/lists/subscribe)

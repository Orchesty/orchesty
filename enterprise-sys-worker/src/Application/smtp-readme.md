# SMTP Connector

An [Orchesty](https://orchesty.io) connector for sending emails via SMTP using the Simple Mail Transfer Protocol, the internet standard for email transmission.

## Application Type

**Basic**

This connector uses the `nodemailer` library to create a transport from a standard SMTP connection URL. It does **not** use HTTP — the `getRequestDto()` method is intentionally unsupported; use `getConnection()` to obtain a `nodemailer.Transporter` instance directly.

| Field | Description |
|---|---|
| `connectionUrl` | Full SMTP connection URL (e.g. `smtp://user:pass@smtp.example.com:587`) |

## Components

| Class | Type | Description |
|---|---|---|
| `SmtpSendEmail` | Connector | Sends an email via the SMTP nodemailer transport using the input JSON body as mail options |

## Setup

### Credentials

1. Obtain SMTP credentials from your email provider (host, port, username, password).
2. Construct the SMTP connection URL in the format: `smtp://username:password@smtp.host.com:port` (or use `smtps://` for SSL).
3. In Orchesty, open the SMTP application settings and enter the **Connection URL**.

**Example connection URLs:**
- Gmail: `smtp://user%40gmail.com:password@smtp.gmail.com:587`
- Mailgun: `smtp://postmaster%40mg.example.com:password@smtp.mailgun.org:587`
- SendGrid: `smtp://apikey:SG.xxxxx@smtp.sendgrid.net:587`

## How It Works

The `SmtpSendEmail` connector does **not** use HTTP. Instead of building an HTTP request through the standard `getRequestDto()` flow, it calls `app.getConnection(appInstall)` to obtain a `nodemailer.Transporter` instance and sends the email directly via `transporter.sendMail()`.

The **entire input JSON body** of the node is passed as-is to `sendMail()` as [nodemailer `MailOptions`](https://nodemailer.com/message/). This means the Orchesty workflow message arriving at `SmtpSendEmail` must be a valid nodemailer mail options object.

### Input payload example

```json
{
  "from": "\"My App\" <noreply@example.com>",
  "to": "customer@example.com",
  "subject": "Your order has been shipped",
  "text": "Hi, your order #1234 has been shipped and will arrive in 2–3 business days.",
  "html": "<p>Hi,</p><p>Your order <strong>#1234</strong> has been shipped and will arrive in 2–3 business days.</p>"
}
```

You can use any field supported by nodemailer, including:

| Field | Description |
|---|---|
| `from` | Sender address (e.g. `"Name" <email>`) |
| `to` | Recipient address(es), comma-separated string or array |
| `cc` / `bcc` | Carbon copy / blind carbon copy recipients |
| `subject` | Email subject line |
| `text` | Plain-text body |
| `html` | HTML body |
| `attachments` | Array of attachment objects (filename, path, content, etc.) |
| `replyTo` | Reply-to address |

For the full list of supported fields, see the [nodemailer message documentation](https://nodemailer.com/message/).

### Output payload example

The node outputs the result returned by nodemailer's `sendMail()`:

```json
{
  "accepted": ["customer@example.com"],
  "rejected": [],
  "messageId": "<b658f8ca-6296-ccf4-8306-87d57a0b4321@example.com>",
  "response": "250 2.0.0 OK"
}
```

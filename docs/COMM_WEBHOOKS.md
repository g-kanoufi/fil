# Communication webhooks (Mailgun + Twilio)

FIL records delivery lifecycle events into `activity_events` (`category=comm`) when provider webhooks fire. No extra setup beyond credentials and route URLs.

## Environment

| Variable | Purpose |
| -------- | ------- |
| `MAIL_MAILER=mailgun` | Outbound email via Mailgun |
| `MAILGUN_DOMAIN`, `MAILGUN_SECRET` | API send |
| `MAILGUN_WEBHOOK_SIGNING_KEY` | Verify event + inbound webhooks (required in staging/production) |
| `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM_NUMBER` | Outbound SMS |
| `TWILIO_AUTH_TOKEN` | Also used to verify Twilio webhook signatures |

Until keys are set, webhooks still accept requests in `local` / `testing` without signatures (see verifiers).

## Routes (configure in provider dashboards)

| Provider | URL | Purpose |
| -------- | --- | ------- |
| Mailgun | `{APP_URL}/api/webhooks/mailgun` | Delivery, open, click, bounce, complaint |
| Mailgun | `{APP_URL}/api/webhooks/mailgun/inbound` | Inbound reply email |
| Twilio | `{APP_URL}/api/webhooks/twilio/inbound` | Inbound SMS |
| Twilio | `{APP_URL}/api/webhooks/twilio/status` | Outbound SMS status (delivered, read, failed) |

Staff settings `GET /api/v1/settings/mail` includes `communication_webhooks.webhook_urls` and readiness flags when keys are present.

## Mailgun events (subscribe in Mailgun → Webhooks)

Handled events (see `config/fil-comm-webhooks.php`):

- `delivered` → comm activity `delivered`
- `failed`, `rejected` → status failed + activity `failed` + suppression
- `complained` → complained + activity `failed`
- `opened` → activity `opened` (meta `opened_at`)
- `clicked` → activity `clicked` (meta `clicked_at`)

Match outbound messages via `external_message_id` (Mailgun `message-id` header). Notification drips can pass `X-FIL-Delivery-Id` / `fil_delivery_id` user variable.

## Twilio status callbacks

Configure the messaging service or number status callback URL to `/api/webhooks/twilio/status`.

Handled statuses:

- `delivered` → activity `delivered`
- `read` → activity `read`
- `undelivered`, `failed` → activity `failed`

Match outbound SMS via `MessageSid` stored as `communications.external_message_id`.

## Idempotency

`webhook_events` stores `(provider, external_event_id)` — Mailgun event `id`, Twilio `{MessageSid}:{status}` — so retries do not duplicate activity rows.

## Activity feed

Webhook rows appear on the global History feed and entity timelines (when subject is a lead). Source is `mailgun_webhook` or `twilio_webhook`; actor is **System**.

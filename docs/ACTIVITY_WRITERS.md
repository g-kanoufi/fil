# Activity writers inventory

Business events go to `activity_events` via `ActivityRecorder`. Page visits use `activity_navigation` (upsert) via `POST /api/v1/activity/page-views`.

## Navigation

| Route | Writer |
| ----- | ------ |
| `POST /api/v1/activity/page-views` | `RecordNavigationJob` → `NavigationActivityRecorder` |

## Business (`ActivityRecorder`)

| Area | Routes | Status |
| ---- | ------ | ------ |
| Auth | login, logout, failed login | Done |
| Profile | `PATCH /profile` | Done |
| Leads | create, update, convert | Done |
| Stores | create, update | Done |
| Contacts | update | Done |
| Closings | update | Done |
| Communications | `POST /communications` | Done |
| FDD | `POST /fdds/{fdd}/leads/{lead}/send` | Done |
| Fields | store, update, destroy | Done |
| Field groups | store, update, destroy | Done |
| Drips | store, update, destroy/pause | Done |
| Notification rules | `PATCH` | Done |
| Royalties | calculate, trigger ACH | Done |
| Import | legacy import command | Done |
| Pipeline phase | projection only (`lead_phase_events`) | By design |
| FDD deliveries / comms on timeline | projection | By design |
| Widget forms, interest regions, bulk FDD, POS sync, mail test | — | Planned follow-up |

## Provider webhooks (`activity_events`, actor System)

| Provider | Events | Writer |
| -------- | ------ | ------ |
| Mailgun | delivered, failed, opened, clicked, … | `CommunicationWebhookService` |
| Twilio | delivered, read, failed, … | `CommunicationWebhookService` |
| Twilio inbound | SMS received | `TwilioWebhookController` + `RecordInboundCommunication` |

See [`COMM_WEBHOOKS.md`](./COMM_WEBHOOKS.md).

## Skipped (noise)

Grid `GET`, health, AI stream, document download, session show.

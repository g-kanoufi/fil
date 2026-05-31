# PII retention & export policy

**Status:** Draft (2026-05-31) — pending client legal sign-off. Covers what personal data FIL stores, how long it is kept, and how subject access/erasure requests are handled. Pair with [SECURITY_AUDIT.md](./SECURITY_AUDIT.md) and [METADATA.md](./METADATA.md).

## Personal data inventory

| Data | Tables (FIL naming) | Sensitivity |
|------|---------------------|-------------|
| Prospect/contact identity | `users`, `leads` (name, email, phone) | Medium |
| Lead activity & comms | `communications`, `activity_events`, `field_values` | Medium |
| Documents (FDD, uploads) | `documents`, `fdd_deliveries` | Medium |
| Bank link tokens | `ach_customers.profile` (**encrypted**) | High |
| POS credentials | `pos_connections.credentials` (**encrypted**) | High |
| Staff accounts | `users` + roles | Medium |

No card/PAN data is stored — ACH flows through Dwolla/Plaid (keeps PCI scope reduced).

## Retention schedule (proposed)

| Category | Retention | Disposal |
|----------|-----------|----------|
| Active lead/contact records | Life of relationship | On erasure request or client policy |
| Closed/lost leads | 24 months (configurable) | Soft-delete → purge job |
| Communications & activity log | 24 months | Purge with parent record |
| Bank/POS link tokens | Until unlink, then immediate | Token cleared on unlink |
| Suppression list (opt-out) | Indefinite (compliance) | Never (legal basis: do-not-contact) |
| Audit/activity events | 24 months min (compliance) | Archive then purge |

Retention windows are **configurable per client** and must be confirmed with the client's legal/DPA before production.

## Subject access requests (SAR / export)

- **Export today:** staff can export the activity feed via `GET /api/v1/activity/export` (CSV/JSON, franchise-scoped). Lead/contact/store grids export via the staff UI.
- **Per-subject export (planned):** a single-subject export bundle (profile + comms + activity + documents list) is not yet a one-click action; assemble from the subject timeline + grids until built.

## Erasure / right-to-be-forgotten

1. Verify the requester's identity and legal basis.
2. Soft-delete the lead/contact and dependent comms/activity.
3. Clear encrypted bank/POS tokens (unlink with provider first).
4. Keep the suppression-list entry (do-not-contact) — this is intentionally retained.
5. Log the erasure action in the audit trail (who, when, subject).

## Open items before production

- [ ] Client legal confirms retention windows + DPA.
- [ ] Implement scheduled purge job for expired soft-deleted records.
- [ ] One-click per-subject export bundle (SAR helper).
- [ ] Document data-processing agreements for sub-processors (Mailgun, Twilio, Dwolla, Plaid, AI proxy).

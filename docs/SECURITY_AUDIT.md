# FIL security audit & remediation plan

> **Date:** 2026-05-30 · **Status re-verified:** 2026-05-31  
> **Scope:** Full application review — auth, API, embed, communications, documents, AI, **financial (Dwolla / Plaid / ACH / royalties / POS)**  
> **Method:** Static code review + route/policy tracing (no dynamic pentest yet)  
> **Status:** Core P0/P1 fixes **implemented and verified**; several items remain **PARTIAL** and SEC-021/022 are future/open. See the [Remediation status](#remediation-status-verified-2026-05-31) table below — the per-item sections further down are the original rationale/spec, not the current state.

Cross-links: [AUTH.md](./AUTH.md) · [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md) · [MVP_DEPLOY.md](./MVP_DEPLOY.md)

---

## Executive summary

FIL’s **session auth + grid scoping + policy layer** are a solid Laravel foundation, but several **money-adjacent and webhook paths are stub-grade** and must be hardened before live ACH.

| Tier | Count | Block go-live? |
|------|-------|----------------|
| **P0 — Critical** | 3 | Yes (financial + webhooks) |
| **P1 — High** | 9 | Yes for franchise rollout; partial for internal-only |
| **P2 — Medium** | 8 | Before production cutover |
| **P3 — Low / future** | 5 | POS go-live; defense-in-depth |

**Top 3 stop-ship items**

1. **Dwolla webhook has zero signature verification** — anyone can mark transfers processed/failed.
2. **Twilio webhooks unverified** — forged inbound SMS and delivery status.
3. **Franchise scope gaps on list/activity/comms endpoints** — grid is scoped; several REST lists are not (IDOR for `area_rep` and future roles).

---

## Remediation status (verified 2026-05-31)

Each SEC item below was re-checked against the current code. **Status legend:** ✅ Done · 🟡 Partial · ⛔ Open · 🔭 Future.

| SEC | Status | Implementing code | Covering test |
|-----|--------|-------------------|---------------|
| 001 Dwolla webhook sig | ✅ | `DwollaSignatureVerifier`; `DwollaWebhookController` (idempotency via `WebhookEvent`) | `tests/Feature/Webhooks/DwollaWebhookTest.php` |
| 002 Twilio webhook sig | ✅ | `TwilioSignatureVerifier`; `TwilioWebhookController` | `tests/Feature/Webhooks/TwilioWebhookTest.php` |
| 003 Plaid token at rest | 🟡 | `AchCustomer` `encrypted:array` cast; `AchCustomerResource` omits token; `PlaidWebhookController` verifies JWT | `SecurityHardeningTest`, `PlaidWebhookTest` — *webhook is verify-and-log only; no ITEM/AUTH handling; no key-rotation doc* |
| 004 List endpoint scope | ✅ | `ResourceScopeService` in Lead/Store/Fdd/Closing/AchTransfer `index` | `SecScopeEndpointsTest`, `ResourceScopeServiceTest` |
| 005 Activity subject IDOR | ✅ | `ActivitySubjectAuthorizer`; `ActivityController::forSubject` | `SecScopeEndpointsTest`, `ActivityControllerTest` |
| 006 Comms lead boundary | ✅ | `CommunicationController` `authorize('view',$lead)` + scoped list | `CommunicationControllerTest`, `SendCommunicationTest` |
| 007 Financial store policy | ✅ | `authorize('view',$store)` on ACH/royalty/POS controllers | `SecScopeEndpointsTest`, `FinancialApiTest` |
| 008 ACH idempotency | 🟡 | unique `(store_id, royalty_period_id)`; `TriggerAchTransfer` correlation_id+dedupe; `AchTransferBatchService` confirmed-status guard; UI keeps trigger disabled after success (`StoreDetailPage`) | `SecScopeEndpointsTest` (dup trigger) — *no failed-Dwolla batch test (server is idempotent + dedupes)* |
| 009 Dwolla enroll trust | ✅ | `AchDwollaEnrollmentService::enroll`; `getCustomer` throws on non-2xx | `AchDwollaEnrollmentTest` |
| 010 Client-token allowlist | ✅ | `DwollaClientTokenRequest::ALLOWED_ACTIONS` | `AchDwollaEnrollmentTest` (not-configured + disallowed-action) |
| 011 Embed fail-closed | ✅ | `ValidateEmbedSiteKey` (env + active widget-form DB keys; staff-only `/embed-demo`); auto `pk_live_*` per form + rotate API | `SecurityHardeningTest`, `WidgetDemoAccessTest`, `WidgetFormTest`; `mvp:staging-check` |
| 012 Login throttle | ✅ | `ThrottleStaffLogin` (10/min); `SessionController` records `auth/login_failed` activity + per-email+IP `RateLimiter` lockout (5/attempt, prod/staging) | `SecurityHardeningTest`, `FailedLoginAuditTest` |
| 013 Disable prod sandbox | ✅ | `AppServiceProvider` (no sandbox bind in prod/staging) | boot-time (no test) |
| 014 Document scope | ✅ | `DocumentPolicy::view` → `canViewDocument` | `DocumentScopeTest` |
| 015 preview_url redirect | ✅ | `PreviewUrlValidator`; `DocumentDownloadController` | *no dedicated redirect test* |
| 016 FDD send authz | ✅ | `FddController::sendToLead` `authorize('view',$lead)` | `FddControllerTest` |
| 017 AI thread authz | ✅ | `AiThreadController::store` `authorize('view',$lead)` | `AiThreadControllerTest` |
| 018 Session cookie | ✅ | `config/session.php`; `mvp:staging-check` flags insecure cookies; `.env.example` documents the prod `SESSION_SECURE_COOKIE=true` / `SESSION_ENCRYPT=true` requirement | `MvpStagingCheckCommandTest` |
| 019 Public intake abuse | 🟡 | `StoreLeadRequest` reCAPTCHA(prod)+honeypot; `PublicLeadResource` | `LeadIntakeControllerTest` — *global 60/min only; no per-site-key limit* |
| 020 Notification XSS | 🟡 | `sanitizeHtml.ts` now uses **DOMPurify** (strips scripts/handlers/`javascript:` URLs); `HtmlSanitizer.php` on save; `SecurityHeaders` middleware (nosniff, frame DENY, referrer, COOP) | `sanitizeHtml.test.ts` (incl. `javascript:` case) — *strict CSP still deferred: needs an allowlist for Plaid/Dwolla/reCAPTCHA + the embed widget, plus browser validation* |
| 021 POS credentials | 🔭 | `PosConnection` `encrypted:array` cast; sync stub | — *OAuth/PKCE/store-scope/SSRF guards before POS go-live* |
| 022 Grid AI PII | ✅ | `GridSearchInterpreterService` PII-redacts the outgoing query (emails/phones) and no longer forwards raw `grid_config`; only field schema + scope tier leave the app | `GridSearchInterpreterServiceTest` (redaction + payload assertion) |
| 023 Legacy import guard | 🟡 | `LegacyImportCommand` blocks `--execute` in prod/staging w/o `--force` | `LegacyImportCommandTest` — *no typed confirmation/audit log* |
| 024 Webhook throttle | ✅ | all webhook routes `throttle:120,1` | — *(edge/WAF out of scope)* |
| 025 FDD PDF validation | 🟡 | `MinimalPdf::isValid` on write; `Content-Disposition: attachment`; `PdfFile` magic-byte rule on `StoreFddRequest`/`UpdateFddRequest` uploads | `MinimalPdfTest`, `FddControllerTest` (non-PDF rejection) — *no virus scan (future)* |

### Remaining local hardening (no Forge/creds needed)

The 2026-05-31 hardening pass closed SEC-012, SEC-018, SEC-020 (XSS), SEC-022, and SEC-025 (magic-byte), and tightened SEC-008. What still remains locally:

1. **SEC-003** — implement Plaid ITEM/AUTH webhook handling (verify-and-log only today) and document `APP_KEY` rotation impact on stored tokens (started in `SECRETS_ROTATION.md`).
2. **SEC-020 (CSP)** — add a Content-Security-Policy once an allowlist for the external scripts (Plaid Link, Dwolla drop-ins, reCAPTCHA) and the embed widget is vetted in a real browser. Safe headers (nosniff, frame DENY, referrer, COOP) ship now via `SecurityHeaders`.
3. **SEC-008** — a failed-Dwolla batch test (server is already idempotent + dedupes; this is test coverage, not a fix).
4. **SEC-019 / SEC-023** — per-site-key intake limiting and typed-confirmation/audit on legacy import.

Items requiring external services (pentest, prod credential verification, Dwolla/Plaid/Twilio live keys) stay blocked — see [Out of scope](#out-of-scope-this-pass).

### Dependency audit (CI gate)

`composer audit --no-dev` and `npm audit --audit-level=high` run in CI and now **fail the build** on advisories (previously `|| true`, non-blocking). Current state: **0 advisories** on both.

- **npm:** resolve by upgrading the offending package; the gate only trips on **high/critical**.
- **composer:** to temporarily accept a specific advisory (e.g. an unpatched transitive dep), run `composer config audit.ignore.<ADVISORY_ID> "reason + ticket"` and call it out in the PR. Re-audit when a fix ships.

---

## What’s working well

- Sanctum SPA + CSRF (`statefulApi`, `X-XSRF-TOKEN` in frontend client)
- `EnsureStaffAccess` + `StaffPolicy` on staff API groups
- `ResourceScopeService` + policies on **grid queries** and recent auth work
- Mailgun webhooks use HMAC + timestamp window (`MailgunSignatureVerifier`)
- Public API CORS limited to `api/public/*` (staff cookie not exposed cross-origin)
- POS `PosConnectionResource` omits `credentials` from API responses
- Financial batch jobs default **off** (`FIL_ENABLE_ACH_COLLECTION`, `FIL_ENABLE_ROYALTY_CALC_JOB`)
- `mvp:staging-check` flags demo users, embed keys, Sanctum domains

---

## P0 — Critical (fix before any live money)

### SEC-001 — Dwolla webhook spoofing

| | |
|---|---|
| **Route** | `POST /api/webhooks/dwolla` |
| **File** | `app/Http/Controllers/Webhooks/DwollaWebhookController.php` |
| **Issue** | No `X-Request-Signature-SHA-256` verification; updates `ach_transfers` and `ach_customers` by external ID |
| **Impact** | Attacker marks royalties paid without transfer; toggles KYC status |

**Fix plan**

1. Add `DwollaSignatureVerifier` (mirror `MailgunSignatureVerifier`) using Dwolla webhook secret.
2. Env: `DWOLLA_WEBHOOK_SECRET`; reject if missing in `production`/`staging`.
3. Store processed webhook event IDs (idempotency table) — reject replays.
4. PHPUnit: valid signature passes; invalid/missing → 403; replay → 409.
5. Rate-limit webhook route (`throttle:120,1` per IP as belt-and-suspenders).

---

### SEC-002 — Twilio webhook spoofing

| | |
|---|---|
| **Routes** | `POST /api/webhooks/twilio/inbound`, `.../status` |
| **File** | `app/Http/Controllers/Webhooks/TwilioWebhookController.php` |
| **Issue** | No `X-Twilio-Signature` validation |
| **Impact** | Inject SMS into lead threads; falsify delivery status |

**Fix plan**

1. Add `TwilioSignatureVerifier` using auth token + full URL + POST params.
2. Env: `TWILIO_AUTH_TOKEN` (already likely present for send path).
3. Extend existing `TwilioWebhookTest` with signature cases (Mailgun tests as template).
4. Optional: IP allowlist Twilio egress ranges as secondary control.

---

### SEC-003 — Plaid access tokens in plaintext

| | |
|---|---|
| **File** | `app/Services/Ach/AchPlaidLinkService.php` |
| **Storage** | `ach_customers.profile` JSON (`plaid_access_token`, `plaid_item_id`) |
| **Impact** | DB/backup compromise → full bank link access |

**Fix plan**

1. Encrypt at rest: Laravel `encrypted:array` cast on `profile`, or dedicated `EncryptedString` for token fields only.
2. Key rotation doc: `APP_KEY` rotation requires re-link or migration command.
3. Never log `profile` or Plaid responses containing tokens.
4. Add Plaid webhook handler (`/api/webhooks/plaid`) with JWT verification for `ITEM`, `AUTH`, `TRANSACTIONS` events (pending verification flow today is incomplete).
5. PHPUnit: token not present in `AchCustomerResource` JSON; DB column encrypted.

---

## P1 — High (before franchise multi-role production)

### SEC-004 — Franchise scope missing on REST list endpoints

Grid queries apply `ResourceScopeService`; **simple index endpoints do not**.

| Endpoint | Controller | Gap |
|----------|------------|-----|
| `GET /api/v1/leads` | `LeadController::index` | No `applyLeadScope` |
| `GET /api/v1/stores` | `StoreController::index` | No `applyStoreScope` |
| `GET /api/v1/fdds` | `FddController` | Global aggregates |
| `GET /api/v1/closings` | `ClosingController` | Permission-only policy |
| `GET /api/v1/ach-transfers` | `AchTransferController` | Global list, optional filter |

**Fix plan**

1. Introduce `ScopedQuery` trait or extend controllers to call `ResourceScopeService` on every list query.
2. Prefer **deprecating** redundant list endpoints where grid covers the use case; or gate behind `admin`/`franchisor` only.
3. PHPUnit matrix: `area_rep`, `franchisee` vs `franchisor` on each index — assert row counts match grid.

---

### SEC-005 — Activity timeline IDOR

| | |
|---|---|
| **Route** | `GET /api/v1/activity/subjects/{type}/{id}` |
| **File** | `ActivityController::forSubject` |
| **Issue** | Only checks `app.access`; no `LeadPolicy::view` / store policy |

**Fix plan**

1. Resolve subject model; `$this->authorize('view', $subject)`.
2. Global feed (`GET /activity`) — filter events to subjects visible via scope service.
3. Feature tests with out-of-scope lead/store IDs → 403.

---

### SEC-006 — Communications without lead boundary

| | |
|---|---|
| **Routes** | `GET/POST /api/v1/communications` |
| **Issue** | `CommunicationPolicy` checks permission only, not target `lead_id` |

**Fix plan**

1. On create/list: `$this->authorize('view', $lead)` when `lead_id` present.
2. `SendStaffCommunication` action: assert lead view before send.

---

### SEC-007 — Financial endpoints lack store policy checks

| | |
|---|---|
| **Routes** | `/stores/{store}/ach/*`, royalty trigger, POS sync |
| **Issue** | `AchPolicy` / `RoyaltyPolicy` are global permissions; no `$this->authorize('view', $store)` |

**Fix plan**

1. Every store-nested financial route: `authorize('view', $store)` minimum; `manage` for mutations.
2. Align with `StorePolicy` + `ResourceScopeService::canViewStore`.
3. Extend `FinancialApiTest` + new scope tests for `area_rep`.

---

### SEC-008 — ACH trigger idempotency

| | |
|---|---|
| **Files** | `TriggerAchTransfer.php`, `RoyaltyController::triggerAch`, `AchTransferBatchService.php` |
| **Issues** | Duplicate clicks → duplicate transfers; `correlation_id` not persisted; batch sets `payment_status = 1` even if Dwolla fails |

**Fix plan**

1. Unique constraint `(store_id, royalty_period_id)` on `ach_transfers` or check before create.
2. Persist Dwolla `correlation_id` on transfer row.
3. Batch: set `payment_status = 1` only when `provider_status` in `processed`, `pending`, `sandbox_queued` with explicit rules; failed → leave unpaid + alert.
4. Frontend: disable trigger button after success; show existing transfer link.

---

### SEC-009 — Dwolla customer binding trust

| | |
|---|---|
| **File** | `AchDwollaEnrollmentService::enroll` |
| **Issue** | Accepts arbitrary `external_customer_id`; `getCustomer()` failure returns `status: unknown` |

**Fix plan**

1. Require client token flow: store pending customer in session/cache keyed to store + user; enroll only matches that ID.
2. Fail enrollment if Dwolla `getCustomer` non-2xx (do not swallow as `unknown`).
3. Validate drop-in callback server-side before persisting.

---

### SEC-010 — Dwolla client-token proxy

| | |
|---|---|
| **File** | `AchCustomerController::dwollaClientToken` |
| **Issue** | Raw JSON body forwarded to Dwolla — privilege amplification |

**Fix plan**

1. Allowlist actions: `customer.create`, `customer.update`, `customer.fundingsources.create` (exact set from drop-in docs).
2. Form request validation; reject unknown keys.

---

### SEC-011 — Embed site-key bypass when allowlist empty

| | |
|---|---|
| **File** | `ValidateEmbedSiteKey.php` lines 21–26 |
| **Issue** | Empty `FIL_EMBED_SITE_KEYS` → any non-empty key accepted |

**Fix plan**

1. In `production`/`staging`: **fail closed** if allowlist empty (`403` + log).
2. `mvp:staging-check`: already flags `pk_dev`; extend to fail on empty list.
3. Document required client-specific keys in deploy runbook.

---

### SEC-012 — Login brute force

| | |
|---|---|
| **Route** | `POST /api/v1/session` |
| **Issue** | No throttle on staff login |

**Fix plan**

1. `throttle:10,1` on session store (per IP + optional per email via RateLimiter).
2. Consider Laravel `Lockout` after N failures; audit log failed attempts.

---

## P2 — Medium (pre-production cutover)

### SEC-013 — Production sandbox fallback for Dwolla/Plaid

| | |
|---|---|
| **File** | `AppServiceProvider` — binds sandbox clients when creds missing |
| **Risk** | Misconfigured prod appears to work; no real money movement |

**Fix:** In `production`, throw on boot if financial features enabled but Dwolla/Plaid not configured; never bind sandbox.

---

### SEC-014 — Document access without franchise scope

`DocumentPolicy::view` === permission only. Franchisee with `documents.view` may access all tenant documents.

**Fix:** Tie documents to `lead_id` / `store_id` / `area_id` and apply scope service.

---

### SEC-015 — Document `preview_url` open redirect

`DocumentDownloadController` redirects to `extras.preview_url` when url-only.

**Fix:** Allowlist hosts (CDN, S3); block private IP ranges; or serve via signed proxy only.

---

### SEC-016 — FDD send without lead authorization

`SendFddDeliveryRequest` authorizes FDD only, not target lead.

**Fix:** `$this->authorize('view', $lead)` in controller.

---

### SEC-017 — AI thread arbitrary `lead_id`

`CreateAiThreadRequest` uses `exists:leads,id` only.

**Fix:** Authorize lead view; redact/limit context sent to `FIL_AI_SERVICE_URL`; document data-processing agreement.

---

### SEC-018 — Session cookie hardening

`.env.example`: `SESSION_ENCRYPT=false`, `SESSION_SECURE_COOKIE` unset.

**Fix:** Production template requires `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, consider `SESSION_ENCRYPT=true`.

---

### SEC-019 — Public lead intake abuse

reCAPTCHA optional; 60/min throttle; response may expose internal IDs.

**Fix:** Require reCAPTCHA in production; honeypot field; minimal public response DTO (no internal IDs); per-site-key rate limits.

---

### SEC-020 — Stored XSS in notification rules admin

`NotificationRulesAdminPage.tsx` uses `dangerouslySetInnerHTML` on `body_html`.

**Fix:** Sanitize HTML (DOMPurify) server-side on save + client render; CSP headers.

---

## P3 — Low / future (POS & defense-in-depth)

### SEC-021 — POS credentials storage (future)

| | |
|---|---|
| **Schema** | `pos_connections.credentials` JSON |
| **Status** | Sync job is stub; no OAuth routes yet |

**Before POS go-live**

1. Encrypt credentials column; separate refresh tokens from access tokens.
2. OAuth state parameter + PKCE; store-scoped connection binding.
3. `PosPolicy` with store scope (not just `stores.manage`).
4. Sync job: validate revenue payloads; no user-controlled URLs (SSRF).
5. Audit log for sync triggers and revenue writes affecting royalties.

---

### SEC-022 — Grid AI interpret data disclosure

`GridSearchInterpreterService` sends grid config + user id to external AI.

**Fix:** Strip PII from payload; on-prem or trusted AI only in production.

---

### SEC-023 — Legacy import commands

CLI-only but destructive (`legacy:import --execute`).

**Fix:** Require `APP_ENV=local` or `--force` + typed confirmation; audit log; separate production DB role without import permission.

---

### SEC-024 — Webhook rate limits

Mailgun/Dwolla/Twilio webhook routes have no throttle.

**Fix:** Global webhook throttle + WAF rule at edge.

---

### SEC-025 — FDD PDF upload validation

MIME-only PDF check.

**Fix:** Magic-byte validation; virus scan hook; serve with `Content-Disposition: attachment`.

---

## Financial stack — targeted hardening checklist

Use this as a **go/no-go gate** before enabling `FIL_ENABLE_ACH_COLLECTION=true`.

### Dwolla

- [ ] Webhook HMAC verified (SEC-001)
- [ ] OAuth or static token in secrets manager (not git)
- [ ] `DWOLLA_DESTINATION_FUNDING_SOURCE_ID` set and validated
- [ ] Sandbox client **disabled** in production (SEC-013)
- [ ] Customer enroll verifies Dwolla customer belongs to store (SEC-009)
- [ ] Client-token allowlist (SEC-010)
- [ ] Transfer idempotency + correlation_id persisted (SEC-008)
- [ ] Manual + batch trigger audited (who, when, amount, period)

### Plaid

- [ ] Access tokens encrypted at rest (SEC-003)
- [ ] Webhook endpoint + JWT verification for item/login events
- [ ] `PLAID_WEBHOOK_URL` points to verified route
- [ ] Processor token flow tested end-to-end with Dwolla funding source
- [ ] Item deletion / re-link overwrites handled safely

### ACH / Royalties

- [ ] Store + period scoped authorization (SEC-007)
- [ ] Unique transfer per royalty period (SEC-008)
- [ ] Batch marks paid only on confirmed provider status
- [ ] Amount override capped to period total on manual trigger
- [ ] Reconciliation job: compare local `ach_transfers` vs Dwolla API daily

### POS (before first adapter)

- [ ] Credentials encrypted (SEC-021)
- [ ] OAuth hardening design reviewed
- [ ] Revenue sync cannot drive ACH without franchisor approval
- [ ] POS revenue feeds royalty calculation — validate signing / tamper detection on inbound webhooks

---

## Remediation phases (suggested order)

### Phase A — Stop-ship (1–2 weeks)

| ID | Task | Est. |
|----|------|------|
| SEC-001 | Dwolla webhook verification + tests | 1d |
| SEC-002 | Twilio webhook verification + tests | 0.5d |
| SEC-003 | Encrypt Plaid tokens | 1d |
| SEC-011 | Embed fail-closed in prod | 0.5d |
| SEC-013 | Disable financial sandbox in prod | 0.5d |

**Exit criteria:** Webhook forgery tests pass; staging check fails on missing secrets.

### Phase B — Franchise IDOR (1 week)

| ID | Task | Est. |
|----|------|------|
| SEC-004 | Scope all list endpoints or deprecate | 2d |
| SEC-005 | Activity subject authorization | 1d |
| SEC-006 | Communications lead boundary | 0.5d |
| SEC-007 | Store policy on financial routes | 1d |
| — | PHPUnit role × endpoint matrix | 1d |

**Exit criteria:** `area_rep` / `franchisee` tests mirror grid scope on all read paths.

### Phase C — Financial logic (1–2 weeks, before live ACH)

| ID | Task | Est. |
|----|------|------|
| SEC-008 | ACH idempotency + batch status fix | 2d |
| SEC-009 | Dwolla enroll verification | 1d |
| SEC-010 | Client-token allowlist | 0.5d |
| SEC-003b | Plaid webhook handler | 1d |
| — | Reconciliation command + alert | 1d |

**Exit criteria:** Duplicate trigger test fails safely; batch integration test with HTTP fake Dwolla.

### Phase D — Production hardening (parallel with Phase 2 comms)

| ID | Task | Est. |
|----|------|------|
| SEC-012 | Login throttle | 0.5d |
| SEC-014–016 | Document / FDD scope | 2d |
| SEC-018–019 | Session + embed intake | 1d |
| SEC-020 | XSS sanitize notification HTML | 0.5d |
| SEC-024–025 | Webhook throttle + PDF validation | 1d |

### Phase E — POS prep (before adapter work)

| ID | Task | Est. |
|----|------|------|
| SEC-021 | POS credential encryption + OAuth design doc | 2d |
| — | Threat model review with Square/Clover API docs | 1d |

---

## Verification plan

1. **Automated:** Extend PHPUnit — webhook signatures, scope matrix, ACH idempotency, encrypted Plaid round-trip.
2. **E2E:** Playwright — forbidden on out-of-scope URLs (already started); add comms send denial.
3. **Manual staging:** Run `./scripts/e2e-smoke.sh` + financial sandbox flows as franchisor vs area_rep.
4. **External:** Trail of Bits–style pass on Phase C before first real transfer (recommended for money movement).
5. **Ongoing:** Add SEC IDs to `parity-checklist.md` / `PRODUCTION_READINESS.md` §6.1 as fixes land.

---

## Mapping to PRODUCTION_READINESS

| Readiness item | Security items |
|----------------|----------------|
| 6.1 Threat model | This document |
| 3.x Financial / ACH | SEC-001, 003, 007, 008, 013, financial checklist |
| 2.x Communications | SEC-002, 006 |
| 1.x Auth / access | SEC-004, 005, 007, 012 |
| Embed / widget | SEC-011, 019 |

---

## Out of scope (this pass)

- Infrastructure (Forge, TLS, WAF, DB encryption at rest) — VPS provider responsibility
- Dependency CVE scan (run `composer audit` / `npm audit` in CI separately)
- Penetration test execution — plan only
- SOC2 / PCI — FIL should **not** store card data; ACH via Dwolla/Plaid keeps PCI scope reduced but not zero

---

*Phase A (webhooks + Plaid encryption + prod fail-closed) and the Phase B IDOR/scope work are implemented and verified — see [Remediation status](#remediation-status-verified-2026-05-31). Next local steps are the ranked items under "Remaining local hardening"; live-money (Phase C) and pentest remain blocked on external credentials.*

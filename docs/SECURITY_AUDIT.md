# FIL security audit

> **Re-verified:** 2026-06-01 · **Scope:** auth, API, embed, comms, documents, AI, financial (Dwolla / Plaid / ACH / royalties / POS)

Cross-links: [AUTH.md](./AUTH.md) · [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md) · [MVP_DEPLOY.md](./MVP_DEPLOY.md)

---

## Summary

**Local code hardening is complete** for SEC-001–025 (see status table). Remaining work requires external services: live Dwolla/Plaid/Twilio credentials, CSP browser validation on staging, pentest, and POS OAuth design (SEC-021) before POS go-live.

Optional local tests (not blocking staging): dedicated Pest test for SEC-015 open-redirect; boot test for SEC-013 sandbox disable; Playwright comms send denial for out-of-scope user.

---

## Remediation status (verified 2026-05-31)

**Legend:** ✅ Done · 🟡 Partial · 🔭 Future

| SEC | Status | Implementing code | Covering test |
|-----|--------|-------------------|---------------|
| 001 Dwolla webhook sig | ✅ | `DwollaSignatureVerifier`; `DwollaWebhookController` | `DwollaWebhookTest` |
| 002 Twilio webhook sig | ✅ | `TwilioSignatureVerifier`; `TwilioWebhookController` | `TwilioWebhookTest` |
| 003 Plaid token at rest | ✅ | `AchCustomer` encrypted cast; `PlaidWebhookController` | `PlaidWebhookTest`, `SecurityHardeningTest` |
| 004 List endpoint scope | ✅ | `ResourceScopeService` on index controllers | `SecScopeEndpointsTest` |
| 005 Activity subject IDOR | ✅ | `ActivitySubjectAuthorizer` | `ActivityControllerTest` |
| 006 Comms lead boundary | ✅ | `CommunicationController` lead authorize | `CommunicationControllerTest` |
| 007 Financial store policy | ✅ | `authorize('view',$store)` on ACH/royalty/POS | `FinancialApiTest` |
| 008 ACH idempotency | ✅ | unique constraint; batch status guard | `SecScopeEndpointsTest`, `FinancialSchedulerCommandsTest` |
| 009 Dwolla enroll trust | ✅ | `AchDwollaEnrollmentService::enroll` | `AchDwollaEnrollmentTest` |
| 010 Client-token allowlist | ✅ | `DwollaClientTokenRequest::ALLOWED_ACTIONS` | `AchDwollaEnrollmentTest` |
| 011 Embed fail-closed | ✅ | `ValidateEmbedSiteKey` + origin guard | `EmbedOriginGuardTest`, `mvp:staging-check` |
| 012 Login throttle | ✅ | `ThrottleStaffLogin` + lockout | `SecurityHardeningTest`, `FailedLoginAuditTest` |
| 013 Disable prod sandbox | ✅ | `AppServiceProvider` | boot-time (*no dedicated test*) |
| 014 Document scope | ✅ | `DocumentPolicy::view` → `canViewDocument` | `DocumentScopeTest` |
| 015 preview_url redirect | ✅ | `PreviewUrlValidator` | *no dedicated redirect test* |
| 016 FDD send authz | ✅ | `FddController::sendToLead` | `FddControllerTest` |
| 017 AI thread authz | ✅ | `AiThreadController::store` | `AiThreadControllerTest` |
| 018 Session cookie | ✅ | `config/session.php`; `mvp:staging-check` | `MvpStagingCheckCommandTest` |
| 019 Public intake abuse | ✅ | reCAPTCHA, honeypot, site-key throttle | `LeadIntakeControllerTest` |
| 020 Notification XSS | ✅ | DOMPurify + CSP builder | `sanitizeHtml.test.ts`, `SecurityHardeningTest` |
| 021 POS credentials | 🔭 | encrypted cast; sync stub | OAuth/PKCE before POS go-live |
| 022 Grid AI PII | ✅ | `GridSearchInterpreterService` redaction | `GridSearchInterpreterServiceTest` |
| 023 Legacy import guard | ✅ | `--force` + `--confirm=legacy-import` | `LegacyImportGuardTest` |
| 024 Webhook throttle | ✅ | `throttle:120,1` on webhook routes | — |
| 025 FDD PDF validation | 🟡 | magic-byte + `Content-Disposition: attachment` | `FddControllerTest` — *no virus scan* |

Per-item rationale and original specs: git history before 2026-06-01 doc trim.

---

## Dependency audit (CI gate)

`composer audit --no-dev` and `npm audit --audit-level=high` **fail the build** on advisories. Current state: **0 advisories** on both.

---

## Financial stack — go/no-go before live ACH

Enable `FIL_ENABLE_ACH_COLLECTION=true` only when all checked on **staging with sandbox keys**, then re-verify on production:

### Dwolla

- [x] Webhook HMAC verified (SEC-001)
- [ ] OAuth/token in secrets manager (not git)
- [ ] `DWOLLA_DESTINATION_FUNDING_SOURCE_ID` set and validated
- [x] Sandbox client disabled in production (SEC-013)
- [x] Customer enroll verifies Dwolla customer (SEC-009)
- [x] Client-token allowlist (SEC-010)
- [x] Transfer idempotency + correlation_id (SEC-008)
- [ ] Manual + batch trigger audited in staging with real sandbox transfers
- [ ] Reconciliation job: compare `ach_transfers` vs Dwolla API daily

### Plaid

- [x] Access tokens encrypted at rest (SEC-003)
- [x] Webhook endpoint + JWT verification
- [ ] `PLAID_WEBHOOK_URL` points to verified staging route
- [ ] Processor token flow tested end-to-end with Dwolla funding source
- [ ] Item deletion / re-link handled safely in browser

### ACH / Royalties

- [x] Store + period scoped authorization (SEC-007)
- [x] Unique transfer per royalty period (SEC-008)
- [x] Batch marks paid only on confirmed provider status
- [ ] Amount override capped — verify on staging
- [ ] Reconciliation command + alert

### POS (before first adapter)

- [x] Credentials encrypted at rest (SEC-021 partial)
- [ ] OAuth hardening design reviewed
- [ ] Revenue sync cannot drive ACH without franchisor approval
- [ ] Inbound webhook signing validated

---

## Verification plan (remaining)

1. **Automated (optional local):** SEC-015 redirect test; SEC-013 boot test; comms denial E2E.
2. **Manual staging:** `./scripts/e2e-smoke.sh` + financial sandbox as franchisor vs `area_rep`.
3. **CSP:** [MVP_DEPLOY CSP checklist](./MVP_DEPLOY.md#csp-live-validation-plaid--dwolla--recaptcha) with Plaid/Dwolla sandbox.
4. **External:** Pentest before first real money transfer (recommended).

---

## Out of scope

- Infrastructure (Forge, TLS, WAF, DB encryption at rest) — VPS provider
- Penetration test execution — vendor engagement
- SOC2 / PCI — FIL does not store card data; ACH via Dwolla/Plaid

---

## Mapping to PRODUCTION_READINESS

| Readiness | Security |
|-----------|----------|
| Phase 6 | This document |
| Phase 3 financial | SEC-001, 003, 007, 008, 013, financial checklist |
| Phase 2 comms | SEC-002, 006 |
| Phase 1 auth | SEC-004, 005, 007, 012 |
| Embed / widget | SEC-011, 019 |

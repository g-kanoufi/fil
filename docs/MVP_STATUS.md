# MVP status snapshot

Brief status for “what works today.” **Full roadmap and % complete:** [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md).

**Last updated:** 2026-05-28

---

## Summary

| Area | Status |
| --- | --- |
| Staff SPA (grids, details, auth) | ☑ Shipped |
| FDD (single + bulk) | ☑ Shipped |
| SMS/email composer | ☑ Shipped |
| Activity timeline (lead + contact) | ☑ Shipped |
| Staging / client import | ☐ Not done |
| Production comms hardening | Partial (queued send + Mailgun webhooks) |

**Production readiness:** ~**52%** — see dashboard in [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md).

---

## In scope for MVP (code complete)

- [x] Laravel API + Sanctum SPA auth
- [x] React staff app: leads, contacts, deals, closings grids
- [x] Lead/contact/deal detail pages
- [x] Custom fields on **leads**
- [x] FDD delivery (single + bulk selection)
- [x] Communication composer on lead detail
- [x] Entity activity timeline (audit + phase/comms/FDD merge)
- [x] AI search proxy (optional external service)
- [x] Admin: users, roles, mail settings, UI catalog
- [x] PHPUnit + Vitest test suites green

---

## Deferred or stub

| Item | Notes |
| --- | --- |
| Contact custom fields | P-002; leads done first |
| Slide-over detail panel | ☑ Grid row → `?panel=` slide-over; full pages at `/reports/:resource/:id` |
| Financial / Dwolla flows | Phase 3; stub UI only |
| Client DB import on staging | Phase 4 |
| Mailgun webhooks, SMS prod | Phase 2 (partial — webhooks + queued staff send) |

---

## Next actions (priority order)

1. **Phase 0** — staging VPS + smoke ([MVP_DEPLOY.md](./MVP_DEPLOY.md))
2. **Phase 4** — client import on staging
3. **Phase 2** — mail/SMS hardening
4. **Phase 5** — Sentry + E2E smoke

Do not expand MVP feature scope until Phase 0 exit checklist is green unless client explicitly requests.

---

## Related docs

- [README.md](./README.md) — documentation index
- [parity-checklist.md](./parity-checklist.md) — legacy parity IDs
- [LOCAL_DEV.md](./LOCAL_DEV.md) — run locally

# Legacy import dry-run checklist

Runbook for **Phase 4 — Data & import quality** ([PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)). Use this before writing any client data to staging or production.

**Related:** [schema-mapping.md](./schema-mapping.md) · [METADATA.md](./METADATA.md) · [MVP_DEPLOY.md](./MVP_DEPLOY.md) · [LOCAL_DEV.md](./LOCAL_DEV.md)

---

## Dry-run semantics

`legacy:import` is **dry-run by default**. It counts rows that *would* be imported and prints a summary table. Nothing is written unless you pass **`--execute`**.

There is no `--dry-run` flag — omit `--execute` instead.

| Mode | Command | Writes DB? |
| --- | --- | --- |
| Dry-run (default) | `php artisan legacy:import` | No |
| Execute | `php artisan legacy:import --execute` | Yes |
| Staging/prod execute | `php artisan legacy:import --execute --force` | Yes (requires explicit `--force`) |

Other import commands follow the same pattern:

| Command | Dry-run | Execute |
| --- | --- | --- |
| `legacy:import-access` | default | `--execute` |
| `legacy:import-acf` | N/A (always writes field schema) | always applies |
| `legacy:import-stream` | (planned) | `--execute` |

---

## Prep without client dump (local / staging)

Run before the dump arrives — validates schema, US/CA interest regions, unit status catalog, and tier-1 spot-check on demo or post-import data:

```bash
cd backend
php artisan legacy:prep
# CI-style (fail on WARN, e.g. missing dump path):
php artisan legacy:prep --strict
```

Full local gate (Pint, tests, OpenAPI, frontend, optional E2E):

```bash
./scripts/phase4-prep-local.sh
# Skip Playwright when browsers not installed:
SKIP_E2E=1 ./scripts/phase4-prep-local.sh
```

`mvp:staging-check` also reports **Interest regions** and **Legacy client dump** (WARN until dump is uploaded).

---

## Pre-import (Z dump parity)

Before postmeta `--execute` on a client dump:

```bash
cd backend
php artisan legacy:sync-acf-json                    # refresh resources/legacy-acf from z-acf-sync
php artisan legacy:acf-catalog                      # manifest → docs/legacy-acf-site-9-manifest.json
php artisan legacy:meta-hygiene ../data/client.sql.gz --post-type=store
php artisan legacy:mapping-gaps ../data/client.sql.gz --entity=store --post-type=store
php artisan legacy:infer-fields ../data/client.sql.gz --post-type=store
php artisan legacy:import-baseline-compare            # golden JSON regression (CI)
```

After schema import: `legacy:import-acf` then entity/postmeta/options imports per [PHASE4_PROD_DATA.md](./PHASE4_PROD_DATA.md).

---

Full multisite exports are often multi-GB. Keep only **site 9** (`vnzokz0zw_9_*`) plus network tables (`users`, `usermeta`, `site`, `sitemeta`, `blogs`, `blogmeta`):

```bash
# Input: repo-root mysql.sql (5GB+) or data/mysql.sql
./tools/slim-legacy-dump.sh mysql.sql data/client-site9.sql.gz
# ~300–400MB gzip typical for PrimeIV site 9
```

Upload `data/client-site9.sql.gz` to staging (not the full `mysql.sql`).

---

## Prerequisites

Before the first dry-run:

- [ ] **Client dump obtained** — latest `.sql.gz` from legacy CRM (see Phase 4.1).
- [ ] **Dump readable** — path set in `.env` or passed as argument:

  ```bash
  FIL_LEGACY_DUMP_PATH=../data/client-site9.sql.gz
  FIL_LEGACY_TABLE_PREFIX=vnzokz0zw_9_   # site 9 default; confirm with client
  ```

- [ ] **ACF JSON available** — `FIL_LEGACY_ACF_PATH` or `backend/resources/legacy-acf/*.json`.
- [ ] **Local or staging FIL stack** — migrations applied (`php artisan migrate --force` on staging).
- [ ] **Inventory script works:**

  ```bash
  cd backend
  php artisan legacy:inventory
  # or: php ../tools/inventory-dump.php data/local.sql.gz --prefix=vnzokz0zw_9_
  ```

- [ ] **PHPUnit green** (sanity): `php artisan test --compact`

---

## Recommended pipeline order

Run dry-runs first, then execute in this order on a **fresh migrated DB** (no demo data mixed with client data on staging).

| Step | Command | Purpose |
| --- | --- | --- |
| 1 | `legacy:inventory` | Legacy post types, custom tables, row counts |
| 2 | `legacy:import-acf` | Field groups + fields schema (needed before postmeta → field_values) |
| 2b | `legacy:sync-interest-region-terms` | Link grabba_tax_area term IDs before postmeta (auto-runs with `--only=postmeta`) |
| 3 | `legacy:import-access --execute` | Roles, permissions, UI grants (after fresh migrate) |
| 4 | `legacy:import` (dry-run) | Full counts — **record output** |
| 5 | `legacy:import --only=…` (dry-run) | Per-entity counts if debugging gaps |
| 6 | `legacy:import --execute` | Write all entities |
| 7 | `legacy:parity-report` | FIL vs legacy count comparison |
| 7b | `legacy:parity-report --samples` | Post-import field_values, interest_region, extras, documents spot checks |
| 7c | `legacy:spot-check` | Sample N leads/stores with tier-1 fields for Zorzees UI spot-check |
| 7c | `legacy:mapping-gaps --entity=store` | Unmapped postmeta audit — see [LEGACY_MAPPING_GAPS.md](./LEGACY_MAPPING_GAPS.md) |
| 8 | `legacy:finalize` | Extras JSON drain check |
| 9 | `legacy:finalize --strict` | Fail CI if extras remain |
| 10 | `mvp:staging-check` | App health after import |
| 11 | Manual UI spot-check | 10 leads, 5 stores, 3 contacts (MVP_DEPLOY) |

### Scoped dry-runs (debugging)

```bash
# Posts / core CRM entities
php artisan legacy:import --only=leads,stores,areas,organizations,fdds,closings

# Users (staff only by default; add --all-users for prospects)
php artisan legacy:import --only=users
php artisan legacy:import --only=users --all-users

# Meta → columns / field_values / extras
php artisan legacy:import --only=postmeta

# Comms, docs, financial
php artisan legacy:import --only=communications,notifications,documents
php artisan legacy:import --only=royalties,ach,ach_enrollment
php artisan legacy:import --only=ai_threads
```

**All `--only` values:** `leads`, `stores`, `areas`, `organizations`, `franchise_locations`, `fdds`, `closings`, `users`, `communications`, `notifications`, `postmeta`, `ai_threads`, `royalties`, `ach`, `ach_enrollment`, `documents`.

---

## Dry-run checklist (copy for staging sign-off)

### A. Environment

- [ ] Dump path and table prefix verified against client site ID
- [ ] Target DB is **empty of demo seed** (use migrate fresh or dedicated staging DB)
- [ ] `APP_ENV` documented (`local` vs `staging`)
- [ ] Backup/snapshot taken before any `--execute` on shared staging

### B. Inventory baseline

- [ ] `legacy:inventory` output saved (attach to ticket)
- [ ] Post type counts match client expectations (applications, stores, areas, …)
- [ ] Custom tables present (`z_communications`, `ach_transfers`, etc.)

### C. Schema prep

- [ ] `legacy:import-acf` run; field group count reasonable
- [ ] `legacy:sync-interest-region-terms --execute` — grabba_tax_area term IDs linked
- [ ] `legacy:import-access --execute` run on fresh DB
- [ ] Roles from [AUTH.md](./AUTH.md) login smoke test passes after access import

### D. Full dry-run

- [ ] `legacy:import` (no `--execute`) completes without error
- [ ] Output table reviewed — every expected entity has non-zero **Matched** (or documented zero)
- [ ] Skipped counts understood (see command output for `skipped *` rows)
- [ ] Dry-run counts within agreed delta of inventory (± client-approved tolerance)

### E. Execute + verify (staging only)

- [ ] `legacy:import --execute` on staging (add `--force` if `APP_ENV=staging`)
- [ ] `legacy:parity-report` — deltas documented in sign-off sheet
- [ ] `legacy:finalize` — note rows still in `extras` JSON
- [ ] Re-run `legacy:import --only=postmeta --execute` if extras remain
- [ ] `legacy:finalize --strict` passes before production cutover
- [ ] `mvp:staging-check` passes
- [ ] UI spot-check: leads grid filters, lead detail, store detail, documents tab, login as franchisor + lead_owner

### F. Production cutover (see MVP_DEPLOY)

- [ ] Legacy CRM in maintenance / read-only
- [ ] Final dump taken after freeze
- [ ] `legacy:import --execute --force` on production FIL
- [ ] Parity report + client sign-off archived
- [ ] Rollback plan documented (DNS revert, read-only legacy window)

---

## Success criteria

| Check | Target |
| --- | --- |
| Parity deltas | Within client-agreed tolerance ([parity-checklist.md](./parity-checklist.md)) |
| `legacy:finalize --strict` | Zero entities with non-empty `extras` |
| Spot-check UI | Pipeline phase, owner, FDD status, store status match legacy samples |
| Auth | Staff roles map correctly; prospects blocked from `/app` |
| No demo users | DemoSeeder not run on staging/prod (`APP_ENV=production` skips it) |

---

## Troubleshooting

| Symptom | Action |
| --- | --- |
| `Dump not readable` | Fix `FIL_LEGACY_DUMP_PATH` or pass absolute path as first argument |
| `Refusing legacy import in staging/production without --force` | Expected safety gate — add `--force` only after backup |
| Large delta on users | Re-run with `--all-users` if prospects required |
| High `extras` count after import | Run `--only=postmeta`; promote keys per [METADATA.md](./METADATA.md) |
| Documents empty | Import order: posts/users first, then `--only=documents` |
| Parity report script missing | Ensure `tools/inventory-dump.php` exists at repo root |

---

## Example session (local)

One-shot (dry-run by default; add `EXECUTE=1` to write):

```bash
./scripts/phase4-import-client.sh data/client-site9.sql.gz
EXECUTE=1 ./scripts/phase4-import-client.sh data/client-site9.sql.gz
```

Manual steps:

```bash
cd backend

# 1. Baseline
php artisan legacy:inventory ../data/client-site9.sql.gz

# 2. Schema + access (on fresh DB)
php artisan migrate:fresh --force
php artisan legacy:import-acf
php artisan legacy:sync-interest-region-terms ../data/local.sql.gz --execute
php artisan legacy:import-access --execute

# 3. Dry-run full import
php artisan legacy:import ../data/local.sql.gz

# 4. Execute + verify
php artisan legacy:import ../data/local.sql.gz --execute
php artisan legacy:parity-report ../data/local.sql.gz --samples
php artisan legacy:mapping-gaps ../data/local.sql.gz --entity=store
php artisan legacy:mapping-gaps ../data/local.sql.gz --entity=lead
php artisan legacy:finalize
php artisan legacy:finalize --strict
php artisan mvp:staging-check
```

---

## Sign-off template

| Entity | Legacy (inventory) | Dry-run matched | FIL after execute | Delta | Accepted (Y/N) | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| leads | | | | | | |
| stores | | | | | | |
| users (staff) | | | | | | |
| communications | | | | | | |
| documents | | | | | | |

**Signed:** _______________ **Date:** _______________

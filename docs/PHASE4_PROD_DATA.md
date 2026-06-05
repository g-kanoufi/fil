# Phase 4 — production dump import

**Last updated:** 2026-06-04

## 1. Slim the dump (local, once)

Full export is at repo root as `mysql.sql` (~5.6GB). Do not commit or upload it.

```bash
./tools/slim-legacy-dump.sh mysql.sql data/client-site9.sql.gz
# ~300–400MB — site 9 (vnzokz0zw_9_*) + network users/blogs/site tables
```

Optional symlink so `./data/mysql.sql` works as input:

```bash
ln -sf ../mysql.sql data/mysql.sql
```

## 2. Local import

```bash
# Dry-run (review counts)
./scripts/phase4-import-client.sh data/client-site9.sql.gz

# Execute (destructive — migrate:fresh + import)
EXECUTE=1 ./scripts/phase4-import-client.sh data/client-site9.sql.gz
```

**Staged execute** (recommended; postmeta is slow):

```bash
cd backend
export FIL_LEGACY_DUMP_PATH=../data/client-site9.sql.gz

php artisan migrate:fresh --force
php artisan db:seed --class=Database\\Seeders\\InterestRegionSeeder
php artisan legacy:import-acf
php artisan legacy:sync-interest-region-terms "$FIL_LEGACY_DUMP_PATH" --execute
php artisan legacy:import-access --execute

php artisan legacy:import --only=leads,stores,areas,organizations,franchise_locations,fdds,closings --execute
php artisan legacy:import --only=users --execute
php artisan legacy:import --only=communications,notifications,royalties,ach,ach_enrollment,ai_threads --execute
php artisan legacy:import --only=documents --execute
php artisan legacy:import --only=postmeta --execute   # 10M+ meta rows — allow 30–90+ min

php artisan legacy:parity-report "$FIL_LEGACY_DUMP_PATH" --samples
php artisan legacy:spot-check --samples=10
php artisan legacy:finalize
```

## 3. Forge staging (`fil.on-forge.com`)

```bash
# From laptop
scp data/client-site9.sql.gz forge@YOUR_FORGE_HOST:/home/forge/imports/client-site9.sql.gz

# On server (repo deploy path)
cd /home/forge/fil.on-forge.com
# .env: FIL_LEGACY_DUMP_PATH=/home/forge/imports/client-site9.sql.gz

FORCE=1 EXECUTE=1 ./scripts/phase4-import-client.sh /home/forge/imports/client-site9.sql.gz
```

Take a DB snapshot before `EXECUTE=1` on staging. Remove `@fil.test` demo users if any remain.

## 4. After import — verification

| Step | Command |
| --- | --- |
| Parity | `php artisan legacy:parity-report ../data/client-site9.sql.gz --samples` |
| Spot-check | `php artisan legacy:spot-check --samples=10` |
| Extras drain | `php artisan legacy:finalize --strict` |
| Health | `php artisan mvp:staging-check` |
| UI | 10 leads, 5 stores, 3 contacts ([MVP_DEPLOY.md](./MVP_DEPLOY.md)) |

## 5. Next engineering (real data loaded)

1. **Parity sign-off** — archive `legacy:parity-report` + client delta tolerance.
2. **Staff login** — import creates users from legacy; set passwords via reset or map one admin from legacy caps.
3. **Staging smoke** — [FORGE_STAGING_CHECKLIST.md](./FORGE_STAGING_CHECKLIST.md) §0.8 with prod-shaped grids.
4. **Documents / uploads** — if files missing, set `FIL_LEGACY_UPLOADS_PATH` and re-run `--only=documents`.
5. **Postmeta extras** — `legacy:finalize`; promote hot keys per [METADATA.md](./METADATA.md).
6. **Cutover** — maintenance window, final incremental dump (optional), production `--force` import.

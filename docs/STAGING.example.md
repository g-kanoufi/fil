# FIL staging environment (template)

Copy to **`STAGING.local.md`** in this folder (gitignored) and fill in real values.

```bash
cp docs/STAGING.example.md docs/STAGING.local.md
```

---

## Host and paths

| Key | Value |
| --- | --- |
| **Public URL** | https://YOUR-FORGE-HOST |
| **Staff app** | https://YOUR-FORGE-HOST/app |
| **Login** | https://YOUR-FORGE-HOST/app/login |
| **API health** | https://YOUR-FORGE-HOST/api/health |
| **Forge site root** | `/home/forge/YOUR-FORGE-HOST` |
| **Laravel (artisan)** | `/home/forge/YOUR-FORGE-HOST/backend` |
| **Web root** | `/home/forge/YOUR-FORGE-HOST/backend/public` |

Some Forge setups use `…/current/` as site root; this project may deploy with repo root = site root (no `current/`).

---

## Forge env

```env
APP_URL=https://YOUR-FORGE-HOST
SANCTUM_STATEFUL_DOMAINS=YOUR-FORGE-HOST
FIL_LEGACY_DUMP_PATH=/home/forge/imports/client-dump.sql.gz
FIL_LEGACY_TABLE_PREFIX=vnzokz0zw_9_
```

---

## Agent verification

- Health: `GET …/api/health`
- UI: Browser MCP on `/app/login` after staff creds exist
- Checklist: [FORGE_STAGING_CHECKLIST.md](./FORGE_STAGING_CHECKLIST.md)
- Roadmap: [PRODUCTION_READINESS.md](./PRODUCTION_READINESS.md)

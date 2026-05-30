---
paths:
  - "frontend/**/*"
---

# FIL frontend (React)

Practical, readable React — **hooks over classes, small files, explicit types, no clever abstractions.**

## Stack

- React 19, TypeScript 5.9+, Vite 8, Vitest 4
- `@fil/app` (staff SPA), `@fil/widget` (embed)
- Path alias: `@/` → `src/`

## Folder layout

```
src/
  components/
  hooks/            # useSession, useGridQuery — data & UI state
  lib/api/          # fetch client + per-resource API functions
  pages/
  types/
widget/src/         # Separate IIFE bundle — keep tiny
```

## Components & hooks

- Function components only; **named exports**
- Props: `interface XProps { … }` above the component
- Data fetching in hooks; components call hooks, not raw `fetch`
- No API calls inside random components — use `lib/api` or hooks

## API layer (`lib/api/`)

- `client.ts` — shared helpers; credentials `include` for Sanctum
- Throw **`ApiError`** with `status` and body
- Base URL: `import.meta.env.VITE_API_URL ?? '/api'`

## Auth (staff app)

- **`AuthProvider`** loads `GET /api/v1/session`; 401 → `/login`
- **`RequirePermission`** gates pages; **`useCan('leads.view')`** — no hard-coded roles
- Nav from **`user.navigation`** (server-driven); see `docs/AUTH.md`

## Styling (theme tokens)

FIL uses **CSS variables in `index.css`** + **Tailwind in `lib/ui/tokens.ts`**. Theme via `[data-theme='dark']` on `<html>`.

| Need | Edit |
| ---- | ---- |
| Semantic colors | `--color-*` in `index.css` + token in `tokens.ts` |
| Links / buttons / badges | `<TextLink>`, `<Button>`, `<Badge>` |

Do **not** use light-only palette pairs (`bg-brand-50` + `text-brand-700`) or per-component `dark:` for semantic colors — use tokens. Verify UI in **both light and dark**.

## XSS

Sanitize untrusted HTML before `dangerouslySetInnerHTML` — use `lib/security/sanitizeHtml.ts`.

## Testing

- `@testing-library/react` — test behavior, not implementation
- Run: `npm run test:run`

## Widget

- Minimal IIFE; `data-site-key` contract in OpenAPI
- Output to `backend/public/widget/`

Verification: `cd frontend && npm run test:run`

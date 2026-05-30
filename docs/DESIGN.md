# FIL design system

Visual direction for the staff SPA. **Implementation:** `frontend/src/index.css` + `frontend/src/lib/ui/tokens.ts`.

**Last updated:** 2026-05-30

---

## Palette (2 accent colors + neutrals)

| Role | Light | Dark | Usage |
| --- | --- | --- | --- |
| **Primary blue** | `#1d4ed8` | `hsl(214 85% 55%)` | Links, primary buttons, active chips |
| **Soft blue surface** | `#eef4ff` / border `#c7d9f8` | `hsl(222 45% 20%)` | Selected filters, stat cards, callouts |
| **Canvas** | `hsl(214 28% 96%)` | `hsl(222 22% 10%)` | Page background |
| **Surface** | `#ffffff` | `hsl(222 24% 14%)` | Cards, header, inputs |
| **Text** | `hsl(222 28% 11%)` | `hsl(210 20% 96%)` | Body copy |
| **Muted** | `hsl(215 14% 38%)` | `hsl(215 12% 62%)` | Labels, hints |

Status tones (success / warning / error / info) stay as separate paired bg+fg tokens — do not reuse for navigation.

Sidebar stays **fixed dark chrome** (`#252f3f`) in both themes — avoids fighting the main light canvas.

---

## Light-mode readability (done)

- Deeper primary blue for links/buttons on white
- Stronger `--color-muted` (38% lightness vs 42%)
- Softer page gray with clearer card shadow
- Filter tiles: inactive counts use `text-foreground`, active use accent fg
- Stat card brand labels use `accent-soft-muted` instead of generic muted

---

## Phase 2 — Polish (done)

| Task | Status |
| --- | --- |
| Login card gradient border + soft page gradient | ☑ |
| Header bottom accent line | ☑ |
| Grid row hover via CSS vars → AG Grid | ☑ |
| Empty states (`EmptyState` on zero grid rows) | ☑ |
| Focus rings on chips, toolbar, segments | ☑ |

---

## Phase 3 — Brand (client-specific)

| Task | Status |
| --- | --- |
| Client primary from `client_settings` | ☑ CSS vars from `highlightColor` / `linkColor` via public + app-config |
| Logo in sidebar | ☑ `logoUrl` → `BrandMark` in sidebar + login |
| Favicon per tenant | ☑ `faviconUrl` in `client_settings` or `FIL_FAVICON_URL` env |

Public: `GET /api/public/v1/branding` (pre-auth login shell). Authenticated `app-config` re-syncs the same keys.

---

## Rules for contributors

1. **Never** add `dark:` color pairs in components — change `index.css` `[data-theme='dark']` block.
2. **Never** use raw `bg-blue-*` / `text-brand-*` in feature code — use `tokens.ts`.
3. New tinted UI → add CSS var pair in `@theme` + export from `tokens.ts`.
4. Test both themes after token changes ([LOCAL_DEV.md](./LOCAL_DEV.md) browser checklist).

---

## Reference

- Cursor rule: `.cursor/rules/fil-frontend.mdc`
- Components: `Button`, `Badge`, `Alert`, `FormField`, `TextLink` consume tokens

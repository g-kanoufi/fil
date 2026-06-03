# FIL design system

Visual direction for the staff SPA. **Implementation:** `frontend/src/index.css` + `frontend/src/lib/ui/tokens.ts`.

**Last updated:** 2026-06-01

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

Light-mode readability and UI polish (login gradient, grid hover, empty states, focus rings) are implemented in `index.css` + shared components.

---

## Brand (client-specific)

| Feature | Source |
| --- | --- |
| Client primary / link colors | `client_settings` → CSS vars via public branding + `app-config` |
| Logo | `logoUrl` → `BrandMark` in sidebar + login |
| Favicon | `faviconUrl` in `client_settings` or `FIL_FAVICON_URL` env |

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

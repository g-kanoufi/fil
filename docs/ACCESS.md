# FIL UI access (Adminimize + React options parity)

The legacy CRM spread view/access config across **multiple stores**. FIL normalizes them into **typed tables** but returns **compatible payloads** for existing UI adapters.

## Legacy sources

| Source | Legacy storage | Used for |
| ------ | -------------- | -------- |
| **Adminimize** | `mw_adminimize` option | Per-role disabled grid filters, tabs, detail nav, resources |
| **React options** | ACF `react_option` → `get_filtered_react_options()` | Catalog of menu/tab/nav keys Adminimize toggles per role |
| **Field options** | `gg_field_options` JSON | Per-role hidden/readonly ACF fields |
| **ACF options** | `options_*` | Notes visibility, ZAI roles, branding |
| **App bootstrap** | `get_application_options()` + `get_application_menus()` | Branding, top nav, grid column menus → localStorage |

FIL replaces the first four with database tables; branding/menus via `client_settings` + catalog seed.

## FIL tables

| Table | Replaces |
| ----- | -------- |
| `ui_menu_items` | React option catalog (`get_filtered_react_options` domains) |
| `role_ui_grants` | Adminimize `mw_adminimize_disabled_app_*_{role}_items` (inverted: we store **allowed**) |
| `role_note_grants` | `options_roles_can_see_{entity}_notes` |
| `client_settings` | `get_application_options()` branding keys |
| `field_role_rules` | `gg_field_options` hide/readonly (already in schema) |

## API

### Session (per user)

`GET /api/v1/session` includes:

```json
{
  "ui_restrictions": {
    "tabs": ["export_csv"],
    "leads": { "menuItems": [], "subMenuItems": ["leads_awarded_deals"] },
    "stores": { "menuItems": ["store_area"], "subMenuItems": [] },
    "nav_stores": { "menuItems": ["nav_stores_ach"], "subMenuItems": [] },
    "features": { "detail_panel": true, "zai_assistant": true }
  },
  "authorized_notes": {
    "application": { "notes": true, "private_notes": false }
  },
  "user_has_panel_access": true
}
```

`ui_restrictions` uses the **same shape as legacy** `loggedUser.adminimize_disabled_menu_items` (disabled lists). Sidebar / GridToolbar / ContentHeader can port with minimal changes:

```tsx
// legacy
loggedUser?.adminimize_disabled_menu_items?.leads

// FIL
user?.ui_restrictions?.leads
```

### App config (shared)

`GET /api/v1/app-config` returns:

```json
{
  "options": { "brandName": "…", "topBarColor": "…", "enable_zai": true },
  "menus": {
    "top_menus": […],
    "menus_with_columns": { "leads": { "menuItems": {}, "subMenuItems": {} } }
  }
}
```

Maps to legacy localStorage keys `branding` / `menus_with_columns` / `top_menus`.

## Domain mapping (Adminimize → `ui_menu_items.domain`)

| Adminimize / react domain | FIL domain | SPA consumer |
| ------------------------- | ---------- | ------------ |
| `grid_tabs` | `grid_tabs` | GridToolbar, ContentHeader |
| `grid_leads` | `grid_leads` | Sidebar leads filters |
| `grid_stores` | `grid_stores` | Sidebar stores filters |
| `grid_contacts` | `grid_contacts` | Sidebar contacts filters |
| `nav_admin` | `nav_admin` | PanelWrapper, Tickets |
| `nav_stores` | `nav_stores` | Store detail tabs |
| `nav_contacts` | `nav_contacts` | Contact detail tabs |
| (resources) | `resources` | Resources page exclusions |
| features | `features` | Panel, ZAI |

Catalog defaults live in `config/fil-ui-catalog.php`. PrimeIV-specific keys expand during `legacy:import-access` (phase 12) from `mw_adminimize` + ACF react options.

## Multi-role rule

Legacy Adminimize merges disabled items across roles with intersection logic. FIL uses a **permissive union** for staff UX: if **any** role allows a UI item, the user sees it. (Stricter deny-all-across-roles can be added per client if needed.)

## Spatie permissions vs UI grants

| Layer | Purpose |
| ----- | ------- |
| **Spatie permissions** | Route/page access (`leads.view`, `royalties.view`) |
| **UI grants** | Filters, tabs, detail sections within an allowed page |
| **Field role rules** | Form field hide/readonly |

Both apply — e.g. user needs `leads.view` **and** non-disabled `lead_status` filter in `ui_restrictions`.

## Migration (phase 12)

`legacy:import-access` will:

1. Read `mw_adminimize` from dump / legacy export
2. Read ACF `react_option` field list → upsert `ui_menu_items`
3. Invert disabled → `role_ui_grants.allowed`
4. Import `gg_field_options` → `field_role_rules`
5. Import branding ACF options → `client_settings`

Parity report compares disabled tab/filter counts per role against legacy `get_logged_user()` output.

# FIL authentication & authorization

Staff SPA is **logged-in only**. Prospects never see the app — they use the public embed widget.

## Layers (defense in depth)

| Layer | Mechanism |
| ----- | --------- |
| **Web** | Laravel `auth` + `staff` middleware on `/app/*` (except `/app/login`) |
| **API** | `auth:sanctum` + `staff` on `/api/v1/*` staff routes |
| **Login** | `LoginUser` + `accessStaffApp` gate (prospects rejected) |
| **Routes / controllers** | `$this->authorize(...)` via **Policies** and named **Gates** |
| **Form requests** | `authorize()` delegates to gates/policies |
| **Frontend** | `AuthProvider` + `RequireAuth` + `RequirePermission` on routes |
| **UI / fields** | `ui_restrictions` + `field_role_rules` (see `docs/ACCESS.md`) |

## Policies (Jeffrey Way / Laracasts)

Authorization lives in **`app/Policies/`**, registered in `AuthServiceProvider`. Controllers stay thin:

```php
$this->authorize('view', AppConfig::class);
$this->authorize('update', $lead);
Gate::authorize('viewFieldSchemaForEntity', 'lead');
```

| Policy | Target | Abilities |
| ------ | ------ | --------- |
| `StaffPolicy` | (gate) | `accessStaffApp` |
| `LeadPolicy` | `Lead` | `viewAny`, `view`, `create`, `update`, `delete` |
| `StorePolicy` | `Store` | same CRUD pattern |
| `ContactPolicy` | `Domain\Contact` | `viewAny`, `view`, `create`, `update` |
| `DocumentPolicy` | `Domain\Document` | `viewAny`, `view` |
| `FddPolicy` | (gates) | `viewAnyFdd`, `manageFdd` |
| `RoyaltyPolicy` | (gates) | `viewAnyRoyalty`, `manageRoyalty` |
| `AchPolicy` | (gates) | `viewAnyAch`, `manageAch` |
| `FieldSchemaPolicy` | `FieldGroup` / gate | `viewAny`, `viewFieldSchemaForEntity` |
| `AppConfigPolicy` | `Domain\AppConfig` | `view` |
| `SettingPolicy` | `Domain\Settings` | `manage` |
| `AiPolicy` | `Domain\AiAssistant` | `access` |

**Spatie permissions** remain the underlying capability store (`leads.view`, etc.). Policies call `$user->can('leads.view')` via the `ChecksFilPermissions` trait — single place to add ownership scoping later (e.g. lead owners see only their leads).

**Domain marker classes** (`App\Domain\Contact`, etc.) authorize resources that do not have Eloquent models yet.

## Roles (Spatie)

| Role | Staff app | Scope tier | Typical access |
| ---- | --------- | ---------- | -------------- |
| `admin` | Yes | unrestricted | Everything |
| `franchisor` | Yes | unrestricted | CRM, stores, royalties, ACH, FDD, comms, AI |
| `lead_owner` | Yes | unrestricted | Leads, contacts, documents, FDD |
| `area_rep` | Yes | area | Scoped leads/stores/contacts in territory |
| `franchisee` | Yes | store | Own stores/contacts; no leads |
| `storemanager` | Yes | store | Assigned store(s); no leads |
| `employee` | Yes | store | Assigned store(s); no leads |
| `prospect` | **No** | — | Public widget only |

Permissions are defined in `config/fil.php` and seeded via `RolesAndPermissionsSeeder`.

### Demo accounts (local/testing)

Seeded by `DemoSeeder` when `APP_ENV` is `local` or `testing`. All use password `password`.

| Email | Role | Notes |
| ----- | ---- | ----- |
| `admin@fil.test` | admin | Full access |
| `franchisor@fil.test` | franchisor | Full CRM |
| `owner@fil.test` | lead_owner | Leads-focused |
| `area_rep@fil.test` | area_rep | Assigned to Southwest area |
| `franchisee@fil.test` | franchisee | PrimeIV Scottsdale store only |
| `storemanager@fil.test` | storemanager | PrimeIV Phoenix store only |
| `employee@fil.test` | employee | PrimeIV Scottsdale (employee role) |
| `prospect@fil.test` | prospect | Cannot sign in to staff app |

Use these for manual role-matrix testing and Playwright auth specs (`e2e/tests/auth.spec.ts`).

## Session API

`GET /api/v1/session` returns:

```json
{
  "data": {
    "id": 1,
    "email": "staff@fil.test",
    "name": "Staff User",
    "roles": ["lead_owner"],
    "primary_role": "lead_owner",
    "permissions": ["app.access", "leads.view", "…"],
    "navigation": [
      { "id": "dashboard", "label": "Dashboard", "path": "/" },
      { "id": "leads", "label": "Leads", "path": "/reports/leads" }
    ],
    "ui_restrictions": { "…": "see ACCESS.md" },
    "hidden_field_keys": ["internal_margin_notes"],
    "readonly_field_keys": []
  }
}
```

**Navigation is policy-driven** — `config/fil.php` navigation items reference `policy` + `ability` or named `gate` keys; `NavigationService` resolves via `Gate`.

## Fields API

`GET /api/v1/fields?entity=lead` — authorized by `FieldSchemaPolicy` / `viewFieldSchemaForEntity` gate. Returns field groups minus hidden keys for the user's roles.

## vs legacy CRM (simpler)

| Legacy | FIL |
| ------ | --- |
| Redirect login + localStorage bootstrap | Sanctum session + `/api/v1/session` |
| `user_main_role`, `all_roles`, `adminimize_disabled_menu_items` | `primary_role`, `permissions`, `navigation`, `ui_restrictions` |
| Role checks scattered in Grid/Sidebar | Policies + `useCan()` + `isUiDisabled()` |
| `gg_field_options` per role | `field_role_rules` → session + fields API |
| Prospects blocked in PHP template | `accessStaffApp` gate + middleware |
| Multisite `blog_ID`, `user_filters` in every ES query | Single tenant; scope in `GridQueryService` from auth user |

## Frontend usage

```tsx
const { user, can, isUiDisabled, isFieldHidden, isFieldReadonly } = useAuth();

<RequirePermission permission="leads.view">
  <LeadsGrid />
</RequirePermission>
```

### API error codes (SPA routing)

| HTTP | `code` | Frontend action |
| ---- | ------ | ----------------- |
| 401 | `unauthenticated` | Clear session → `/login` |
| 403 | `staff_required` | Clear session → `/login` |
| 403 | `forbidden` | Stay signed in → `/forbidden` |

Implemented in `ApiProblem`, `EnsureStaffAccess`, and `frontend/src/lib/api/authBridge.ts`.

## Data scope (`ResourceScopeService`)

Franchise row boundaries live in **`App\Services\Auth\ResourceScopeService`** — not scattered meta-cap checks.

| Config `fil.scope.tiers` | Roles | Grid/list behavior |
| ------------------------ | ----- | ------------------ |
| `unrestricted` | admin, franchisor, lead_owner | No row filters |
| `area` | area_rep | Leads/stores/contacts in assigned areas |
| `store` | franchisee, storemanager, employee | Assigned stores only; **no leads** |

Policies call the scope service for `view`/`viewAny`; `GridQueryService` applies the same filters server-side.

When building the staff SPA:

- Replace legacy auth context with `useAuth()`
- Replace `adminimize_disabled_menu_items` with `user.ui_restrictions` (same shape — see `docs/ACCESS.md`)
- Load branding/menus from `appConfig` (`GET /api/v1/app-config`) instead of localStorage bootstrap
- Use `user.hidden_field_keys` / `readonly_field_keys` for form field visibility

## Configuring access

1. Add Spatie permission strings in `config/fil.php` → `role_permissions`
2. Add or extend a **Policy** method (do not authorize inline in controllers)
3. Wire navigation via `policy`/`gate` in `config/fil.php` → `navigation`
4. Re-seed: `php artisan db:seed --class=RolesAndPermissionsSeeder`

UI filter/tab hiding is separate — see `UiAccessSeeder` and `docs/ACCESS.md`.

---
paths:
  - "backend/**/*"
---

# FIL backend (Laravel)

Write Laravel the way Jeffrey Way teaches on Laracasts: **thin controllers, fat domain edges, explicit types, feature tests first.**

## Stack

- Laravel 13, PHP 8.4, Pest 4 (PHPUnit 12)
- Sanctum (SPA session), spatie/laravel-permission
- PostgreSQL; queues on `database` driver

## File layout

```
app/
  Actions/{Domain}/          # Single-purpose invokable classes
  Http/Controllers/Api/      # Thin — delegate, return Resources
  Http/Requests/Api/           # Form Requests — all validation here
  Http/Resources/Api/          # JsonResource — API shape only
  Models/                      # Eloquent + scopes, casts, relationships
  Policies/                    # Authorization (not inline in controllers)
  Services/{Domain}/           # Multi-step domain logic
  Jobs/{Domain}/               # Async: ShouldQueue, idempotent where possible
```

| Layer | Use for |
| ----- | ------- |
| Form Request | Input validation, `authorize()`, typed accessors |
| Action | One HTTP/command operation |
| Service | Orchestration reused by Actions, Jobs, Commands |
| Job | Slow, retriable, or webhook-driven work |
| Resource | Model → JSON; never leak Eloquent in controllers |
| Policy | `$this->authorize('update', $lead)` |

## PHP style

- `declare(strict_types=1);` on every new PHP file
- `final` on classes not meant to be extended
- No `env()` outside `config/` — use `config('fil.*')`

## Routes & auth

- Staff routes: `middleware(['auth:sanctum', 'staff'])`
- Permissions + navigation in `config/fil.php`; seed with `RolesAndPermissionsSeeder`
- Franchise scope: use `ResourceScopeService` on list endpoints and policies — see `docs/AUTH.md`
- Throttle public routes and webhooks

## Testing

- Feature tests for every route (happy path + validation + auth failure)
- Use factories and `RefreshDatabase` via `uses(RefreshDatabase::class)` per file
- Pest `test()` / `it()` in `backend/tests`; base case in `tests/Pest.php`
- Run: `php artisan test --compact`

## Do not

- Fat controllers with business logic
- Returning Eloquent models directly from API
- Legacy CMS naming (`blog_ID`, vendor REST paths)
- Generic `meta` JSON for domain fields

Verification: `cd backend && php artisan test --compact`

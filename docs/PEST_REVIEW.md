# Pest — FIL backend test suite

**Date:** 2026-05-30  
**Context:** Laravel 13, PHP 8.4, **~90 test files**, **294 Pest tests**, `php artisan test --compact`.

---

## Status

**Migrated to Pest 4.** The backend suite runs through Pest (on PHPUnit 12). CI unchanged: `php artisan test --compact`.

---

## Stack

| Item | Value |
|------|--------|
| Test runner | Pest 4 (`pestphp/pest`) |
| Laravel integration | `pestphp/pest-plugin-laravel` |
| Underlying engine | PHPUnit 12 (`phpunit/phpunit`) |
| Base case | `tests/Pest.php` → `pest()->extend(Tests\TestCase::class)->in('Feature', 'Unit')` |
| Env / DB | `phpunit.xml` `<php>` block (SQLite in-memory) |
| CI | `.github/workflows/ci.yml` → `php artisan test --compact` |

---

## Conventions

Typical feature test:

```php
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('lead owner can list leads', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->getJson('/api/v1/leads')
        ->assertOk();
});
```

- **`uses(RefreshDatabase::class)`** per file — not global in `Pest.php` (some unit tests skip DB refresh).
- **File-local helpers** — Drift emits `function helperName()` at file bottom; names must be unique suite-wide.
- **HTTP helpers in closures** — use `test()->call(...)` inside standalone helpers when `$this` is unavailable.

---

## Migration notes (2026-05-30)

1. Installed `pestphp/pest`, `pestphp/pest-plugin-laravel`, `pestphp/pest-plugin-drift`.
2. Bulk conversion via Drift (programmatic run; CLI `--drift` had argv parsing issues in this environment).
3. Manual fixes:
   - Duplicate global helpers (`admin()`, `lead()`) renamed per file.
   - `DocumentsBrowserTest.php` — Drift left a partial PHPUnit class; flattened to Pest functions.
   - `DwollaWebhookTest.php` — `$this->call()` in helper → `test()->call()`.

---

## Commands

```bash
cd backend
composer install
php artisan test --compact          # full suite
./vendor/bin/pest tests/Feature/Api/LeadControllerTest.php  # single file
./vendor/bin/pint tests             # format test files
```

---

## Related

- [AGENTS.md](../AGENTS.md) — verification commands
- [Pest docs](https://pestphp.com/docs/installation)
- [Pest Laravel plugin](https://pestphp.com/docs/plugins/laravel)

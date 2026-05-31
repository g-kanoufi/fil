# Pest testing standard — FIL backend

**Standard:** All backend tests use **Pest 4** (on PHPUnit 12). New tests MUST be written as Pest test functions — do not add new `extends TestCase` PHPUnit classes.

**Context:** Laravel 13, PHP 8.4, ~90 test files, **300 Pest tests**, run via `php artisan test --compact`.

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

## Required conventions

- **Test functions, not classes.** Write `test('...', function () { ... })` (or `it(...)`). Never add new PHPUnit `*Test` classes — convert legacy ones to Pest when you touch them.
- **`uses(RefreshDatabase::class)` per file** — not global in `Pest.php`, because some unit tests skip DB refresh.
- **Seed roles in `beforeEach`** for feature tests that hit authorization: `$this->seed(RolesAndPermissionsSeeder::class)`.
- **Unique file-local helpers.** Helpers declared at the bottom of a test file (`function helperName()`) share a global namespace — names must be unique suite-wide.
- **HTTP helpers in standalone closures** — use `test()->call(...)` / `test()->getJson(...)` when `$this` is not available inside a helper.
- **One behaviour per test**; prefer expressive `expect()` assertions over bare PHPUnit asserts where it reads better.

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

---

## Commands

```bash
cd backend
composer install
php artisan test --compact                                   # full suite
./vendor/bin/pest tests/Feature/Api/LeadControllerTest.php   # single file
./vendor/bin/pint tests                                      # format test files
```

---

## Migration notes (2026-05-30)

The suite was migrated from PHPUnit classes to Pest 4:

1. Installed `pestphp/pest`, `pestphp/pest-plugin-laravel`, `pestphp/pest-plugin-drift`.
2. Bulk conversion via Drift (programmatic run; CLI `--drift` had argv parsing issues in this environment).
3. Manual fixes:
   - Duplicate global helpers (`admin()`, `lead()`) renamed per file.
   - `DocumentsBrowserTest.php` — Drift left a partial PHPUnit class; flattened to Pest functions.
   - `DwollaWebhookTest.php` — `$this->call()` in helper → `test()->call()`.

---

## Related

- [AGENTS.md](../AGENTS.md) — verification commands
- [Pest docs](https://pestphp.com/docs/installation)
- [Pest Laravel plugin](https://pestphp.com/docs/plugins/laravel)

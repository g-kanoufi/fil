# FIL stack versions

Keep dependencies on **latest stable** within the constraints below. After upgrades, run `composer test` and `npm run test:run`.

| Layer | Version | Notes |
| ----- | ------- | ----- |
| PHP | **8.4+** | `composer.json` `^8.4`; Forge + CI use 8.4 |
| Laravel | **13.x** | Currently v13.12+ |
| PHPUnit | **12.x** | Laravel 13 compatible (PHPUnit 13 when framework supports it) |
| Sanctum | **4.x** | SPA session auth |
| spatie/laravel-permission | **7.x** | Roles |
| PostgreSQL | **17** | `docker-compose.yml` `postgres:17-alpine` |
| Node.js | **24** | CI; local `>=22` |
| React | **19.x** | Staff SPA + future port |
| TypeScript | **5.9.x** | TS 6 when ecosystem catches up |
| Vite | **8.x** | App + widget builds |
| Vitest | **4.x** | Frontend tests |

## Upgrade commands

```bash
# Backend
cd backend && composer update && php artisan test --compact

# Frontend
cd frontend && npm update && npm run test:run && npm run build
cd widget && npm update && npm run build
```

## Do not downgrade

- Laravel 13 is required (not 11/12).
- React 19 for all staff SPA code.
- PHP 8.4 minimum — Symfony 8 / Laravel 13 components expect 8.4+.

# Project Guidelines — Work Time Tracker

This document explains how this Laravel project is organized and how Junie should work with it (run, test, and verify
changes) before submitting results.

## 1) Project Overview

- Framework: Laravel (PHP)
- Purpose: Time tracking backend with API endpoints. The codebase also includes real‑time messaging/event examples (
  e.g., `Message`, `MessageSent` event) and API routes.
- Entry points:
    - HTTP API routes in `routes/api.php`
    - Console kernel and artisan commands via `artisan`
- Notable components (examples):
    - Models: `app/Models/User.php`, `app/Models/Message.php`
    - Events: `app/Events/MessageSent.php`
    - Controllers: `app/Http/Controllers/...` (e.g., `Api/MessageController.php`)
    - Config: `config/*.php` (e.g., `broadcasting.php`, `logging.php`)
    - Tests: `tests/` with PHPUnit configuration in `phpunit.xml`

## 2) Repository Structure (high level)

- `app/` — Application code (Models, Http Controllers, Services, Events, etc.)
- `bootstrap/` — Framework bootstrap files and cache
- `config/` — Application configuration
- `database/` — Migrations, factories, and seeders
- `public/` — Public web root
- `resources/` — Views and frontend resources
- `routes/` — Route definitions (`api.php`, `web.php` if present)
- `storage/` — Logs, cache, compiled files, API docs (if generated)
- `tests/` — Automated tests (feature/unit)
- `vendor/` — Composer dependencies

## 3) Running the Project Locally

Prerequisites: PHP 8.x, Composer, and a database (MySQL/PostgreSQL/SQLite). Node.js is optional unless frontend assets
are being built.

Typical setup:

1. Install dependencies:
   ```bash
   composer install
   ```
2. Environment:
    - Copy `.env.example` to `.env` if needed and configure DB and cache settings.
    - Generate key if not present:
      ```bash
      php artisan key:generate
      ```
3. Database:
   ```bash
   php artisan migrate --seed
   ```
4. Serve API locally:
   ```bash
   php artisan serve
   ```

Broadcasting/real‑time (optional): configure `BROADCAST_DRIVER` (e.g., `pusher`) in `.env` and corresponding keys if
using events like `MessageSent`.

## 4) How Junie Runs Tests and Verifies Changes

- Test runner: PHPUnit (configured via `phpunit.xml`). You can also use `php artisan test` which wraps PHPUnit.
- Default commands:
    - Fast run:
      ```bash
      php artisan test
      ```
    - Or directly:
      ```bash
      ./vendor/bin/phpunit
      ```
- Database for tests:
    - Prefer SQLite for speed. If using SQLite, set in `.env.testing`:
      ```ini
      DB_CONNECTION=sqlite
      DB_DATABASE=:memory:
      ```
      or point `DB_DATABASE` to `database/database.sqlite` and ensure the file exists.
- When Junie must run tests:
    - Always run tests after modifying PHP code (controllers, models, services, events, routes).
    - For documentation‑only changes, running tests is not required.

## 5) Build/Run Policy Before Submitting

- PHP/Laravel has no compile step; however:
    - If code changed: run the test suite as noted above.
    - If routes or container bindings changed: optionally run `php artisan route:list` or start the app locally to
      sanity‑check.
    - Do not commit generated caches (`bootstrap/cache/*`) or storage logs.

## 6) Coding Style and Conventions

- Follow PSR‑12 and Laravel conventions:
    - Class and file names in StudlyCase, methods in camelCase.
    - Use dependency injection in controllers/services where possible.
    - Keep controllers thin; put domain logic into services/repositories when applicable.
- Formatting:
    - Match existing import order and spacing found in neighboring files.
    - Use strict types where already used in the codebase; otherwise, follow existing patterns.
- Static analysis and linting:
    - If tools are configured (e.g., PHPStan, Pint, or PHP-CS-Fixer), run them. If not, keep to Laravel defaults and
      PSR‑12.

## 7) Common Commands Cheat‑Sheet

```bash
# Run tests
php artisan test
./vendor/bin/phpunit

# Run migrations/seeders
php artisan migrate --seed

# Serve the application
php artisan serve

# Show routes
php artisan route:list
```

## 8) Notes for Real‑Time/Broadcasting

- Broadcasting driver is configured in `config/broadcasting.php` and `.env`.
- If features rely on Pusher or websockets, ensure the related `.env` keys are set before manual testing.

## 9) Contribution Expectations for Junie

- For any non-trivial change:
    - Explain what changed and why in the submission summary.
    - Run tests and include a brief note about their status.
- For trivial documentation or config edits that don’t affect runtime, tests are optional.

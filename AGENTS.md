# AGENTS.md

High-signal guidance for OpenCode sessions in `4toTOP` (Laravel monolith).

## Stack and Tooling (verified from manifests)
- Laravel `^13.0`, PHP `^8.3` (`composer.json`).
- Livewire `^3.6` + Volt `^1.10`; authorization uses `spatie/laravel-permission`.
- Frontend build is Vite 6 + Tailwind 3 + Bootstrap 5 (`package.json`, `vite.config.js`, `resources/css/app.css`).
- Test stack is Pest 4 on PHPUnit 12.
- Repo-local OpenCode config enables Laravel Boost MCP (`opencode.json` + `boost.json`).

## Commands Agents Should Actually Use
- Setup: `composer install && npm install && cp .env.example .env && php artisan key:generate && php artisan migrate`.
- Full local dev loop: `composer run dev` (starts server + queue listener + log tail + Vite concurrently).
- Frontend only: `npm run dev` or `npm run build`.
- Lint gate: `./vendor/bin/pint --test` (there is no ESLint/typecheck script).
- Run tests: `./vendor/bin/pest` (or `php artisan test`).
- Focused test: `./vendor/bin/pest tests/Feature/Auth/AuthenticationTest.php` or `./vendor/bin/pest --filter="users can logout"`.

## Environment and Test Gotchas
- App defaults to MariaDB in `.env.example` (`DB_CONNECTION=mariadb`).
- Cache, queue, and session default to `database`; migrations must be up or app behavior breaks in non-obvious ways.
- `phpunit.xml` forces test DB to MySQL database `sistoficiolibertades_test` (not sqlite). Ensure this DB exists before running test suites.
- Pest global config applies `RefreshDatabase` to all `tests/Feature/*` via `tests/Pest.php`, so feature tests are migration-sensitive.

## Architecture Notes That Prevent Wrong Assumptions
- Primary HTTP wiring is in `routes/web.php`; many modules are route-prefixed groups (`SistOficioLibertades.*`, `seguridad.*`, etc.).
- Codebase includes legacy lowercase controller class/file names (example: `app/Http/Controllers/addfuncionarioController.php`); do not rename unless task explicitly requests refactor.
- Root route `/` is `TurnoController@enviarTurno` named `welcome` (not the default Laravel welcome view).
- Frontend entrypoints are fixed to `resources/css/app.css` and `resources/js/app.js`; JS currently exposes global `jQuery` and `Swal`.
- `AppServiceProvider` contains critical auth behavior: fixed-email superadmin bypass and auto-sync of `SuperAdmin` role/permissions.

## Agent Workflow Expectations
- Minimum verification before handoff: `./vendor/bin/pint --test` + targeted Pest tests for changed behavior.
- Prefer small, low-risk edits over broad rewrites in this legacy-heavy codebase.

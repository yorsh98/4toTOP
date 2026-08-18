# AGENTS.md

## Workflow
- Setup: `composer install && npm install && cp .env.example .env && php artisan key:generate && php artisan migrate`.
- `composer run dev` runs the HTTP server, database queue listener, Pail, Vite, and scheduler together. Use `npm run dev` or `npm run build` only for frontend work.
- Formatting is `./vendor/bin/pint --test`; no JavaScript lint or typecheck script is configured. Run targeted tests with `./vendor/bin/pest path/to/Test.php` or `./vendor/bin/pest --filter="test name"`.
- Feature tests use `RefreshDatabase` globally and `phpunit.xml` overrides the connection to MySQL database `sistoficiolibertades_test`, not SQLite. Migrations and that test database must exist before running them.
- Local defaults use MariaDB and database-backed cache, sessions, and queues; migrate before exercising normal application flows.

## Application Map
- HTTP routes are centralized in `routes/web.php`; feature modules use route prefixes and names such as `SistOficioLibertades.*` and `seguridad.*`. `/` is `TurnoController::enviarTurno`, not Laravel's default welcome route.
- UI is mixed Blade, Livewire classes in `app/Livewire`, and Volt. Volt mounts both `resources/views/livewire` and `resources/views/pages`; auth routes point to `pages.auth.*`.
- Vite builds only `resources/css/app.css` and `resources/js/app.js`. The JS entrypoint intentionally exposes global `jQuery`, `Swal`, and `SwalToast`; CSS imports Bootstrap before Tailwind layers.
- Legacy controller file/class names are lowercase (for example `addfuncionarioController`). Do not rename them incidentally.

## Security And Operations
- Authorization is Spatie permissions. `AppServiceProvider` grants every ability to a fixed email and to users with the `SuperAdmin` role; changes here affect all authorization.
- The web middleware stack appends `App\Http\Middleware\SecurityHeaders` globally.
- Scheduled notification commands live in `routes/console.php`; development's `composer run dev` includes `schedule:work`. Production needs an external `schedule:run` cron and a database queue worker.
- Repo-local OpenCode config starts Laravel Boost MCP with `php artisan boost:mcp`.

## Verification
- Before handoff, run `./vendor/bin/pint --test` and the smallest relevant Pest test file or filter.

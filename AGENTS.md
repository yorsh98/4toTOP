# AGENTS.md

Guide for agentic coding assistants working in `4toTOP`.

## Scope
- This file defines practical build/test/style rules for this repository.
- Use it together with repo config files (`composer.json`, `.editorconfig`, `phpunit.xml`, etc.).

## Stack Snapshot
- Backend: Laravel 11, PHP 8.2.
- UI: Blade + Livewire 3 + Volt.
- Frontend build: Vite + Tailwind.
- Package managers: Composer and npm.
- Test framework: Pest on top of PHPUnit.

## Cursor / Copilot Rules Status
- `.cursor/rules/`: not found.
- `.cursorrules`: not found.
- `.github/copilot-instructions.md`: not found.
- No extra agent rules are currently defined outside this file.

## Environment Setup
Run from repository root:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Optional one-command local dev process:

```bash
composer run dev
```

`composer run dev` starts Laravel server, queue listener, log tailing, and Vite.

## Build Commands

```bash
npm run dev
npm run build
```

## Lint / Format Commands

```bash
./vendor/bin/pint
./vendor/bin/pint --test
php -l app/Http/Controllers/TurnoController.php
```

Notes:
- There is no ESLint script in `package.json`.
- Treat Pint as the canonical PHP formatting/lint gate.

## Test Commands

Run all tests:

```bash
php artisan test
./vendor/bin/pest
```

Run a single test file (preferred for focused changes):

```bash
php artisan test tests/Feature/Auth/AuthenticationTest.php
./vendor/bin/pest tests/Feature/Auth/AuthenticationTest.php
```

Run a single test by name/filter:

```bash
php artisan test --filter="users can logout"
./vendor/bin/pest --filter="users can logout"
```

Run one PHPUnit-style method:

```bash
php artisan test --filter=AuthenticationTest::test_users_can_logout
```

Run a suite:

```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## Testing Conventions
- Prefer Pest `test('behavior...', function () {})` style.
- Keep test names behavior-focused and readable.
- Use model factories for setup (`User::factory()->create()`).
- For Volt pages, follow existing `Volt::test(...)` patterns.
- Keep DB tests deterministic; avoid hidden inter-test dependencies.
- Run targeted tests first, then broader suite if risk is high.

## Code Style: Formatting and Imports
- Follow `.editorconfig`: UTF-8, LF, 4 spaces, final newline.
- Run Pint after PHP changes and before handoff.
- Use explicit `use` imports; avoid unnecessary fully-qualified names inline.
- Remove unused imports in edited files.
- Avoid style-only churn in unrelated code.

## Code Style: Types and Signatures
- In new/edited PHP code, prefer typed parameters and return types.
- Add property types in new classes when practical.
- Keep method contracts explicit at boundaries.
- Align Eloquent `$fillable` and `$casts` with actual stored fields.
- Validate input shape before persistence.

## Code Style: Naming
- New class/file names should follow PSR-4 + StudlyCase.
- Methods, variables, and local properties should use camelCase.
- Respect established DB column and request key naming.
- This repo has legacy non-standard class names (for example lowercase controller/model names).
- Do not rename legacy classes/files unless the task explicitly requests refactoring.

## Error Handling and Validation
- Validate request and Livewire input at boundaries.
- Prefer framework guard helpers (`findOrFail`, `firstOrFail`, `abort_unless`).
- Return clear HTTP statuses (400/403/404/422/500) when appropriate.
- Do not silently swallow exceptions.
- If catching exceptions, log actionable context.
- Keep user-facing error messages concise and context-appropriate.

## Laravel / Livewire Guidance
- Keep controllers thin; move complex business logic to service classes.
- Reuse existing route naming/grouping to avoid collisions.
- Preserve middleware/auth protections on secured routes.
- Keep Livewire state updates predictable and validations centralized.

## Blade / Frontend Guidance
- Keep heavy logic out of Blade templates.
- Use existing Vite entrypoints from `vite.config.js`.
- Keep JS modules small and compatible with current dependencies.
- Avoid introducing new frontend frameworks unless requested.

## Agent Workflow Expectations
- Before handoff, at minimum run:
  - `./vendor/bin/pint --test`
  - targeted tests for the changed behavior
- If full suite is too heavy, report exactly which tests were executed.
- Prefer incremental, low-risk changes over broad refactors.
- Do not add new tooling/config unless required by the task.

Update this document whenever scripts, tooling, or coding conventions change.

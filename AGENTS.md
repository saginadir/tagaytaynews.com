# AGENTS.md

Engineering conventions for agents working in this repo.
**Read `MISSION.md` first** — it explains what we are building, the editorial
rules, and how work is coordinated (`bin/task`).

## Tech stack

PHP 8.4 · Laravel 13 · Vue 3 · Inertia.js 3 · Tailwind CSS 4 · Vite 8 · SQLite ·
Pest 5 (PHPUnit 13) · Wayfinder (TS route/action generation)

## Task tracking

Work is coordinated through `bin/task` (sqlite `tasks.db`, gitignored). Check
`bin/task next` at session start; update statuses and log notes as you go. See
MISSION.md → Operating loop.

## Common commands

- Dev server: `composer dev` (php serve + vite) or each part separately
- SSR build: `npm run build:ssr`
- Tests: `php artisan test --compact` · filter: `--filter=testName`
- Create test: `php artisan make:test --pest {name}`
- Lint PHP: `composer lint` · check only: `composer lint:check`
- Frontend: `npm run lint` / `format` / `types:check`
- Full local CI: `composer ci:check` (also runs in GitHub Actions)

## Planning instructions

For big changes always create a detailed plan and add to the end of the plan:

1. Architecture overview of the solution
2. Task list that is needed to achieve the plan

## Code conventions

- Run `vendor/bin/pint --dirty --format agent` before finalizing changes.
- Create tests with Pest; do NOT delete tests without approval.
- Use PHP 8 constructor property promotion:
  `public function __construct(public GitHub $github) { }`
  No empty zero-parameter constructors unless private.
- Use proper PHP type hints for parameters and return types:
  `protected function myFunc(Obj $obj, ?string $str = null): bool`
- Avoid silent fails: always log errors (`Log::error(...)` with context).

## Project layout notes

- Public site is Inertia + Vue (`resources/js/pages`); most pages still
  starter-kit defaults — being built out (see tasks).
- Admin back-office: secret path from `ADMIN_PATH` (default `x-ops`), config in
  `config/admin.php`, session guard `admin.auth` middleware. Media manager exists
  (`AdminMediaController`, `Media` model).
- Wayfinder regenerates `resources/js/{actions,routes,wayfinder}` (gitignored) —
  don't hand-edit.

## Remote server

Production runs on a Hetzner VPS. Deploy via `devops/deploy.sh`;
provisioning in `devops/provision.sh`; server secrets are in `.env*` files
(never committed). Don't deploy unless the task says to.

## Git

- Never commit/push/reset/rebase unless the user explicitly asks in that session.
- `tasks.db` and `.env*` stay local.

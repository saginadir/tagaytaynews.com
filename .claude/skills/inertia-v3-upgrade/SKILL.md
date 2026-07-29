# Inertia.js v2 to v3 Migration Guide

Migrate a Laravel + Inertia.js v2 application to v3. This guide covers Vue projects but notes React/Svelte differences.

## Prerequisites

- PHP 8.2+
- Laravel 11+
- Vue 3 (React 19+ or Svelte 5+ if using those adapters)

## Step 1: Upgrade Packages

```bash
# Server-side
composer require inertiajs/inertia-laravel:^3.0

# Client-side (pick your adapter)
npm install @inertiajs/vue3@^3.0      # Vue
npm install @inertiajs/react@^3.0     # React
npm install @inertiajs/svelte@^3.0    # Svelte

# Optional: new Vite plugin (auto page resolution + SSR)
npm install @inertiajs/vite@^3.0
```

## Step 2: Republish Config and Clear Views

```bash
php artisan vendor:publish --provider="Inertia\ServiceProvider" --force
php artisan view:clear
```

The config structure changed. Key differences in `config/inertia.php`:
- `testing.ensure_pages_exist` and `testing.page_paths` moved under a `pages` key
- New `pages.ensure_pages_exist`, `pages.paths`, `pages.extensions` keys
- New `expose_shared_prop_keys` and `history.encrypt` options
- SSR config gains `ensure_bundle_exists` and `throw_on_error`

## Step 3: Fix Blade Template Head Attribute

In your root Blade template (usually `resources/views/app.blade.php`):

```html
<!-- v2 -->
<title inertia>{{ config('app.name') }}</title>

<!-- v3 -->
<title data-inertia>{{ config('app.name') }}</title>
```

## Step 4: Check for Removed/Renamed APIs

Search your codebase for these and replace:

### PHP (server-side)

| v2 | v3 | Action |
|---|---|---|
| `Inertia::lazy(...)` | `Inertia::optional(...)` | Rename |
| `LazyProp` class | Removed | Use `Inertia::optional()` |

### JavaScript (client-side)

| v2 | v3 | Action |
|---|---|---|
| `router.cancel()` | `router.cancelAll()` | Rename |
| `router.on('invalid', ...)` | `router.on('httpException', ...)` | Rename event |
| `router.on('exception', ...)` | `router.on('networkError', ...)` | Rename event |
| `onInvalid` callback | `onHttpException` callback | Rename in visit options |
| `onException` callback | `onNetworkError` callback | Rename in visit options |
| `hideProgress()` / `revealProgress()` | Use `progress` object directly | Removed |
| `future` key in `createInertiaApp` | Delete entirely | All future options are now default |
| `require()` imports | `import` statements | v3 is ESM-only |

### Document events

| v2 | v3 |
|---|---|
| `inertia:invalid` | `inertia:httpException` |
| `inertia:exception` | `inertia:networkError` |

## Step 5: Handle Axios Removal

Inertia v3 replaced Axios with a built-in XHR client. Check for:

1. **Axios interceptors on Inertia's client** — migrate to built-in interceptors or use the optional Axios adapter
2. **Direct imports of `axios` from Inertia** — install `axios` as a direct dependency if you still need it elsewhere
3. **`qs` package** — no longer bundled; install directly if your app imports it
4. **`lodash-es`** — replaced with `es-toolkit`; install directly if your app imports it

## Step 6: Optional — Adopt the Vite Plugin

The new `@inertiajs/vite` plugin replaces manual page resolution and SSR config:

```ts
// vite.config.ts
import inertia from '@inertiajs/vite'

export default defineConfig({
    plugins: [
        inertia(),
        // ... other plugins
    ],
})
```

This replaces:
- `resolvePageComponent` from `laravel-vite-plugin/inertia-helpers` in `app.ts`
- Manual SSR server setup (SSR works automatically during `npm run dev`)

If you keep the existing setup (without the Vite plugin), everything still works — the plugin is optional.

## Step 7: Behavioral Changes to Be Aware Of

### `useForm` timing change
The `processing` and `progress` states now stay `true` until the `onFinish` callback fires (previously reset earlier). If you have UI logic that depends on `processing` flipping to `false` before `onFinish`, adjust it.

### React `<Deferred>` component
No longer shows fallback during partial reloads (now consistent with Vue/Svelte). A `reloading` slot prop is available across all adapters.

### ES build target
Changed from ES2020 to ES2022. If you support older browsers, add `@vitejs/plugin-legacy`.

## Step 8: Verify

```bash
# Run tests
php artisan test --compact

# Build frontend
npm run build

# Start dev server and test manually
npm run dev
```

## New Features Available After Migration

- **`useHttp` hook** — standalone HTTP requests without page visits
- **Optimistic updates** — instant UI with automatic rollback on failure
- **Layout props** — share data between pages and layouts via `useLayoutProps`
- **SSR in dev** — works automatically with the Vite plugin (no separate Node server)
- **Exception handling** — render custom Inertia error pages from exception handlers
- **Instant visits** — swap to target component before server responds
- **`preserveErrors`** — keep form errors during partial reloads
- **Blade components** — `<x-inertia::head>` and `<x-inertia::app>` as directive alternatives
- **Smaller bundle** — ~30% smaller JS bundle due to Axios/qs/lodash removal

## Quick Checklist

- [ ] `composer require inertiajs/inertia-laravel:^3.0`
- [ ] `npm install @inertiajs/vue3@^3.0` (or react/svelte)
- [ ] `php artisan vendor:publish --provider="Inertia\ServiceProvider" --force`
- [ ] `php artisan view:clear`
- [ ] `<title inertia>` changed to `<title data-inertia>` in Blade template
- [ ] `Inertia::lazy()` replaced with `Inertia::optional()`
- [ ] `router.cancel()` replaced with `router.cancelAll()`
- [ ] Event names updated (`invalid` → `httpException`, `exception` → `networkError`)
- [ ] `future` config block removed from `createInertiaApp`
- [ ] `hideProgress()`/`revealProgress()` calls removed
- [ ] Axios interceptors migrated (if any)
- [ ] `qs` and `lodash-es` installed directly (if imported)
- [ ] Tests pass: `php artisan test --compact`
- [ ] Frontend builds: `npm run build`

# Origynz — Genealogy Workspace

A web app for researching, mapping, and preserving family history. Build private or shared family tree workspaces with people profiles, relationships, timelines, records, photos, and GEDCOM import/export.

> **Architecture:** Origynz is split into two separately-deployed apps that run in **one DDEV project, two hostnames**:
>
> | URL | App |
> |---|---|
> | `https://origynz.ddev.site` | **React + TypeScript SPA** (Vite) in [`web/`](web/) |
> | `https://origynzapi.ddev.site` | **headless Laravel JSON API** (token / bearer auth) |
>
> The SPA authenticates with Sanctum bearer tokens and talks to the API over CORS. The
> SPA's `web/src/core/` layer (API client, query hooks, auth store, validation) is kept
> platform-agnostic so a future **React Native** app can reuse it unchanged.
>
> The legacy Blade/Livewire UI still ships during the migration; it is retired once the
> SPA reaches full parity.

## Features

- Multi-tree workspaces with member invitations and access control
- Person profiles: relationships, life events, sources, citations
- GEDCOM import and export (async, with progress tracking)
- Media library per tree
- Family statistics dashboard
- Configurable event types
- Activity audit trail
- Social login (Google, GitHub, Facebook, LinkedIn)
- Two-factor authentication and email verification

## Tech Stack

| Layer | Technology |
|---|---|
| API backend | PHP 8.3+, Laravel 13 (JSON only) |
| API auth | Laravel Fortify (headless), Sanctum bearer tokens, Socialite |
| SPA frontend | React 19 + TypeScript, React Router, TanStack Query, Zustand, Tailwind CSS 4 (in `web/`) |
| Legacy UI (being retired) | Livewire 4, Flux 2 |
| Build | Vite, Node 22 |
| Database | MariaDB 10.11 |
| Queue | Redis + Laravel Horizon |
| Web server | Nginx-FPM (routes the two hostnames; proxies the SPA to Vite in dev) |

## Local Development (DDEV)

### Prerequisites

- [DDEV](https://ddev.readthedocs.io/en/stable/) installed
- Docker running

### Setup

```bash
ddev start                # provisions both hostnames + a Vite dev server (web_extra_daemons)
ddev composer setup       # install PHP deps, generate key, migrate
ddev exec 'cd web && npm install'   # install SPA deps (also auto-installs on first boot)
```

- **SPA:** `https://origynz.ddev.site` (Vite dev server with HMR, proxied by nginx)
- **API:** `https://origynzapi.ddev.site` (try `https://origynzapi.ddev.site/api/v1/health`)

DDEV settings management is disabled (`disable_settings_management: true`) so that
`APP_URL` can point at the **API** host while the SPA owns the primary hostname.

### Running the dev environment

The Vite dev server starts automatically with `ddev start`. For the queue/logs:

```bash
ddev exec php artisan horizon      # queue worker (GEDCOM imports)
ddev exec php artisan pail         # log viewer
```

### Environment variables

Copy `.env.example` to `.env` and set the following:

```env
# Social login (at least one required)
SOCIALITE_PROVIDERS=google,github
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI=

# Queue (required for GEDCOM import)
QUEUE_CONNECTION=redis
REDIS_HOST=redis

# Split hosts
APP_URL=https://origynzapi.ddev.site   # the API's own URL
FRONTEND_URL=https://origynz.ddev.site # SPA origin (drives CORS + email links)
```

The SPA reads its API base URL from `web/.env.development` (`VITE_API_URL`).
DDEV provides `DB_*`/`REDIS_*`; with settings management disabled they are kept in `.env`.

## Commands

```bash
ddev composer test               # clear config, lint check, run PHPUnit
ddev composer lint               # auto-fix code style with Pint
ddev composer analyse            # PHPStan static analysis
ddev exec 'cd web && npm run build'   # typecheck + production build the SPA
ddev exec 'cd web && npx tsc --noEmit' # SPA typecheck only
```

## Testing

```bash
ddev composer test
```

Tests run against SQLite by default. The CI matrix covers PHP 8.3, 8.4, and 8.5.

## CI

GitHub Actions runs on push to `develop`, `main`, and `master`:

- **Lint** — PHP Pint code style check
- **Tests** — PHPUnit on PHP 8.3/8.4/8.5 with Node 22
- **react-spa** — installs `web/` deps and typechecks + builds the SPA

## Queue Worker

GEDCOM imports are processed asynchronously. Horizon must be running:

```bash
php artisan horizon
# or via ddev composer dev (starts automatically)
```

## Deployment
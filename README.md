# Origynz

Origynz is a genealogy workspace for researching, mapping, and preserving family history. It supports private and shared family trees, person profiles, relationships, media, GEDCOM import/export, and API-driven account management.

## Current Architecture

The app is now split into a Laravel JSON API and a React SPA. They run in one DDEV project with two local hostnames:

| URL | App |
|---|---|
| `https://origynz.ddev.site` | React + TypeScript SPA in [`web/`](web/) |
| `https://origynzapi.ddev.site` | Headless Laravel API |

The SPA calls `/api/...` relative to its own origin. In DDEV, nginx proxies those requests to `origynzapi.ddev.site`, which keeps the app working through local HTTPS, `ddev share`, and LAN/tunnel access. The legacy Blade/Livewire UI has been retired; Laravel now serves API routes plus a small JSON root response.

## Features

- Multi-tree workspaces with access control
- Person profiles with relationships, events, sources, and citations
- GEDCOM import/export with queued processing
- Media library and signed media access
- Global tree and relationship tools
- Account settings, API tokens, email verification, and two-factor auth
- Admin APIs for super-admin workflows

## Tech Stack

| Layer | Technology |
|---|---|
| API backend | PHP 8.3+, Laravel 13 |
| API auth | Laravel Fortify, Sanctum bearer tokens, Socialite |
| SPA frontend | React 19, TypeScript, React Router, TanStack Query, Zustand |
| Styling/build | Tailwind CSS 4, Vite, Node 22 |
| Database | MariaDB 10.11 in DDEV |
| Queue | Redis queue worker via DDEV `web_extra_daemons` |
| Web server | nginx-fpm, with a custom SPA server block |

## Local Development

### Prerequisites

- DDEV
- Docker

### First Setup

```bash
ddev start
ddev composer setup
ddev exec 'cd web && npm install'
```

`ddev start` also starts:

- the Vite dev server for `web/`
- a Redis queue worker for GEDCOM imports and background media jobs

Visit:

- SPA: `https://origynz.ddev.site`
- API health: `https://origynzapi.ddev.site/api/v1/health`

### Environment

DDEV settings management is disabled because this project needs separate API and frontend URLs. Keep these values in `.env`:

```env
APP_URL=https://origynzapi.ddev.site
FRONTEND_URL=https://origynz.ddev.site
QUEUE_CONNECTION=redis
REDIS_HOST=redis
```

The SPA reads `VITE_API_URL` from `web/.env.development`. It can be blank for normal DDEV use because nginx proxies `/api/` on the SPA host to Laravel.

Social login providers are optional in local development, but production/provider testing needs the relevant `SOCIALITE_PROVIDERS`, client IDs, secrets, and redirect URIs.

## Common Commands

```bash
ddev composer setup              # PHP install, app key, migrations
ddev composer lint               # Pint auto-format
ddev composer test               # config clear, Pint check, Laravel tests
ddev composer analyse            # PHPStan
ddev exec 'cd web && npm run build' # SPA typecheck + production build
ddev exec 'cd web && npm run lint'  # SPA typecheck only
```

Outside DDEV, run frontend commands from `web/`; there is no root `package.json`.

## Testing

```bash
ddev composer test
ddev exec 'cd web && npm run build'
```

The Laravel test suite is API-focused and runs against SQLite by default. The SPA build runs `tsc -b` before `vite build`.

## CI

GitHub Actions currently runs:

- `linter`: installs Composer dependencies and runs Pint.
- `tests`: runs PHPUnit on PHP 8.3, 8.4, and 8.5.
- `react-spa`: installs dependencies in `web/` and runs the SPA production build with Node 22.

Because the frontend package lives in `web/`, CI must use `working-directory: web` for npm steps.

## Deployment Notes

Deploy the API and SPA as separate apps or services:

- API: Laravel app with `APP_URL` set to the API origin.
- SPA: built output from `web/dist`.
- Frontend config: set `FRONTEND_URL` on the API and provide the SPA with the correct API base URL or a reverse proxy for `/api/`.
- Queue: run a Laravel queue worker for GEDCOM imports and background media downloads.

For DDEV production-like serving, `.ddev/nginx_full/origynz-spa.conf` includes commented instructions for serving `web/dist` instead of proxying to Vite.

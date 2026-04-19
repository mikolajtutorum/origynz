# Origynz — Genealogy Workspace

A web app for researching, mapping, and preserving family history. Build private or shared family tree workspaces with people profiles, relationships, timelines, records, photos, and GEDCOM import/export.

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
| Backend | PHP 8.3+, Laravel 13 |
| Auth | Laravel Fortify, Sanctum, Socialite |
| Frontend | Livewire 4, Flux 2, Tailwind CSS 4 |
| Build | Vite 8, Node 22 |
| Database | MariaDB 10.11 |
| Queue | Redis + Laravel Horizon |
| Web server | Nginx-FPM |

## Local Development (DDEV)

### Prerequisites

- [DDEV](https://ddev.readthedocs.io/en/stable/) installed
- Docker running

### Setup

```bash
ddev start
ddev composer setup       # install deps, generate key, migrate, build assets
```

The app will be available at `https://origynz.ddev.site`.

### Running the dev environment

```bash
ddev composer dev         # Laravel server + Horizon + Pail log viewer + Vite HMR
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
```

DDEV injects `DB_*` variables automatically — no manual database config needed.

## Commands

```bash
ddev composer test        # clear config, lint check, run PHPUnit
ddev composer lint        # auto-fix code style with Pint
ddev composer analyse     # PHPStan static analysis
ddev npm run build        # production asset build
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

## Queue Worker

GEDCOM imports are processed asynchronously. Horizon must be running:

```bash
php artisan horizon
# or via ddev composer dev (starts automatically)
```

## Deployment
# 🔗 LinkHub — Community Edition

**Free, self-hosted link and application portal with AD login.**
LinkHub is the central start page for all web applications of a company:
employees find their applications with a short description and manual link,
filtered by areas and departments — without logging in. Managers and
administrators maintain the content after login. The *Community Edition* is
the complete open-source version (AGPL-3.0); all data lives in one SQLite file
on your own server.

*Deutsche Fassung: [README.md](README.md)*

---

## Why?

Bookmark lists on the intranet go stale and new colleagues don't know where to
find which application. LinkHub turns them into a maintained portal:

- **Portal without login**, read-only — it only shows maintained links and
  descriptions, no personal data.
- **Maintained with the Windows account**: roles come from AD groups on every
  login, there are no separate passwords.
- **Traceable**: every change in the audit log with a SHA-256 hash chain.
- **Backed up**: nightly backup with checksums, plus a test system that can
  take over the production state with one click.

## Features

| Area | What it does |
|---|---|
| Portal | tiles with monogram, short description, "Open" and optional manual link; search; light and dark |
| Filters | areas as tabs, below them the departments of the area; "All" shows the whole catalogue |
| Visibility | applications belong to areas and/or departments; without assignment only under "All" |
| Categories | sections on the start page (e.g. "Communication"); without category under "More" |
| Maintenance | applications via form; areas, departments and categories with inline editing |
| Languages | UI German/English with a switcher; optional English names and descriptions for all content |
| Branding | company name (replaces "LinkHub" in the header), logo, header, accent and footer colour, footer text; glass design, light and dark |
| Imprint and privacy | can be switched on individually, as your own text or a link to the company website; reachable without login, with a template for the privacy notice |
| Audit | every create, change and delete plus logins and logouts (spatie/activitylog) with hash chain and verify button |
| Backup | nightly at 01:30, retention configurable (at least 4 days); "Back up now" with checksum file, never deleted automatically |
| Test system | second, separate instance (port 7281) with TESTSYSTEM banner and restore of the latest production state |
| Help | version, licence, manuals and changelog right in the app (footer → **Help**) |
| Sign-in | own accounts (menu **Users**) always; directory optional with “Test connection”; groups as DN or name, nested AD groups; emergency access via `php artisan linkhub:admin-password` |
| Two-factor | TOTP with authenticator app; per group mandatory, optional or off, directory groups with mandatory, exception per user; recovery codes; reset by administrators |
| Fox helper | **Rua the fox** (a Viking fox from a Norwegian fjord village, called “the red one” by the Irish) greets signed-in users, gives tips matching role and page, promotes free software on the sign-in page; not on the public portal; can be switched off centrally and per person |
| Operations | Docker, SQLite (WAL), safe updates with downgrade protection, demo mode without a directory |
| Security | security headers (CSP, HSTS), password-guessing brake per address and per user name, proxy headers only trusted from internal networks |

## Roles

| Role | May |
|---|---|
| **Visitor** | view, search and filter the portal — without login |
| **Manager** | maintain applications, areas, departments and categories |
| **Administrator** | additionally settings, audit log, backup and test system |

Managers and administrators are either **own accounts** (menu **Users**,
permissions ticked) or **directory accounts**: then one group each determines
the role (**Settings → 3 · Roles from groups**, initial values from
`AD_GROUP_MANAGERS` and `AD_GROUP_ADMINS`). The sync runs on every login; with
Active Directory nested groups are resolved as well. Directory accounts are
created automatically on first login.

## Guides

- [Installing with Docker](installation/en/docker.md) — Linux server, mini PC, Docker Desktop, reverse proxy
- [Installing on Synology (Container Manager)](installation/en/synology.md)
- [Operations](installation/en/operations.md) — updates, backups, restore, test system
- Manuals (German): [users](docs/USER-GUIDE.md) · [administrators](docs/ADMIN-GUIDE.md) · [architecture](docs/ARCHITECTURE.md) · [security](docs/SECURITY.md)

## Quick start

You need Docker with Compose.

```bash
git clone https://github.com/steffenmai-maidigital/linkhub.git
cd linkhub
mkdir -p backups
docker compose up -d --build
```

LinkHub is then available at <http://localhost:7280>. Out of the box it runs
in **demo mode** with sample data; log in at the top right with `admin` /
`admin` or `manager` / `manager`.

### Production

1. In `docker-compose.yml` set `DEMO_MODE: "false"`. No `.env` is needed. On
   the first start LinkHub creates the administrator `admin` as an own
   account; the initial password is printed in the container log
   (`docker compose logs app`) or set beforehand as `ADMIN_PASSWORD`.
2. Sign in as `admin` → **Users**: set your own password, create further
   accounts — or enter the directory under **Settings → 2 · Sign-in**, save
   and “Test connection”; roles then come from the groups under
   **Settings → 3**.
3. **Settings**: company and appearance, backup, imprint and privacy.
4. Create the **structure** (areas, departments, categories), then the
   **applications**.

Nobody gets locked out: `docker exec linkhub php artisan linkhub:admin-password`
creates an own admin account or resets its password at any time.

LinkHub generates the `APP_KEY` itself on the first start and stores it in
`/data/.app_key`. Step by step: [installation/en/docker.md](installation/en/docker.md).

Behind a reverse proxy set `APP_URL` to the public address (see
[installation/en/docker.md](installation/en/reverse-proxy.md)).

### Settings (environment variables)

| Variable | Default | Meaning |
|---|---|---|
| `DEMO_MODE` | true (Compose) | demo accounts and sample data, no directory |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | admin / *(empty = random, printed in the log)* | first administrator as own account, only on the very first start |
| `TEST_MODE` | false | marks the instance as "TESTSYSTEM" |
| `APP_NAME` | LinkHub | application name (the company name from the settings replaces it in the header) |
| `APP_URL` | http://localhost:7280 | public address |
| `APP_KEY` | *(empty = generated on first start, stored in `/data/.app_key`)* | only set it if a specific key is required |
| `TRUSTED_PROXIES` | internal networks | reverse proxies whose `X-Forwarded-*` headers are trusted; `*` = all (not recommended) |
| `SOURCE_URL` | *(empty)* | link to the source code in the footer; point it to your own repo if you change the code (AGPL) |
| `GITHUB_URL`, `PROJECT_URL` | GitHub project, www.mai-digital.de | links under **Help** and in the footer |
| `APP_DEBUG` | false | shows error details in the browser — only briefly for troubleshooting |
| `DB_DATABASE` | /data/linkhub.sqlite | database file in the `app-data` volume |
| `BACKUP_PATH` | /backups | backup target (`./backups` in Compose) |
| `LDAP_HOST`, `LDAP_PORT`, `LDAP_SSL`, `LDAP_TLS` | — | optional initial values for the directory (otherwise everything under **Settings → Sign-in**); a set host enables directory sign-in |
| `LDAP_BASE_DN`, `LDAP_USERNAME`, `LDAP_PASSWORD` | — | search base and service account (initial values) |
| `LDAP_ENABLED`, `LDAP_USER_ATTRIBUTE`, `LDAP_USER_FILTER`, `LDAP_BIND_TEMPLATE`, `LDAP_VERIFY_CERT`, `LDAP_TIMEOUT` | — | further initial values, see `.env.example` |
| `AD_GROUP_ADMINS`, `AD_GROUP_MANAGERS` | LinkHub-Admins, LinkHub-Manager | initial role groups (afterwards in the settings) |
| `FRESH_DB` | 0 | `1` deletes the database at start. Set it back to `0` right away |

### Data and backups

- `app-data` (volume): database `linkhub.sqlite`, key `.app_key` and the
  backups taken before every update under `/data/backups/` (the 10 newest are kept)
- `app-storage` (volume): sessions, logs, cache
- `app-uploads` (volume): company logo
- `./backups` (folder, create it first): nightly backups as ZIP with checksum,
  manual backups under `./backups/manual`

Restore and moving: see [installation/en/operations.md](installation/en/operations.md).

### Test system

`docker-compose.test.yml` starts a completely separate second instance on
port 7281 (own volumes, own `backups-test` folder, own image `linkhub:test`)
with an unmistakable TESTSYSTEM banner. The production backup folder is
mounted read-only there; under **Settings → Test system** the latest
production state can be restored with one click.

### Editions and licence

| Edition | What |
|---|---|
| **Community Edition** | the open-source version. Complete, without restrictions and without expiry. |
| **Supported Edition** | the same software with a maintained support licence (Standard or Enterprise). |

The licence is **purely informational and has no functional effect**. Admins
enter it under **Settings → Licence** (licensee and a 20-character key); it is
shown under **Help**.

| Position | Meaning | Example |
|---|---|---|
| 1 | `1` = Standard, `2` = Enterprise | `1` |
| 2–9 | customer number (8 letters/digits) | `MUSTER01` |
| 10–15 | end of support as `YYMMDD` | `270731` |
| 16–20 | licence count (5 digits, `99999` = unlimited) | `00500` |

## Versions and Docker images

Version numbers follow the scheme **YYMMDDNN**: date plus a running number for
that day. `26101701` is the first version on 17 Oct 2026. Older releases used
MAJOR.MINOR.PATCH (up to 2.1.3, see the [changelog](CHANGELOG.md)).

Every build creates the Docker image **`linkhub:latest`**. A released version
is also tagged with its number, e.g. **`linkhub:26101701`**. For every version
GitHub also builds the image for Intel/AMD and ARM and publishes it as
`ghcr.io/steffenmai-maidigital/linkhub:<version>` and `:latest`.

New versions are published by the maintainers; what changes in each version is
listed in the [CHANGELOG](CHANGELOG.md).

## Development

```bash
docker build -t linkhub:dev .
docker run --rm -p 7280:80 -e DEMO_MODE=true -e APP_DEBUG=true linkhub:dev
```

Without Docker:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/data/linkhub.sqlite
DB_DATABASE=$PWD/database/data/linkhub.sqlite DEMO_MODE=true php artisan migrate --seed
DB_DATABASE=$PWD/database/data/linkhub.sqlite DEMO_MODE=true php artisan serve
```

Layout:

```
app/Models            Application, Area, Department, Category, Setting, User, ActivityLog
app/Support           Directory (AD/LDAP, roles from groups on every login), Mfa, Fox, LegalPages, ProductInfo, ErrorPage
app/Services          BackupService, LicenseService
app/Http/Controllers  portal, applications, structure, settings, backup, test system, docs, support, login
resources/views       Blade UI, a single stylesheet in public/css/app.css
lang/en.json          English translation (German strings are the keys)
docs/                 manuals (also shown in the app under "Help")
installation/         guides: Docker, Synology, reverse proxy, operations (en/ = English)
```

No build step: no Node and no npm.

### Translating

All UI strings are written in German and serve as keys. The English strings
live in `lang/en.json`. Please add new strings to
`lang/en.json` right away and keep placeholders such as `:name`; before each
version the maintainers check that everything is translated.
Missing strings fall back to German.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Please do not report security issues
publicly, see [SECURITY.md](SECURITY.md).

## Licence

[GNU Affero General Public License v3.0](LICENSE) (AGPL-3.0-or-later).

You may use, modify and share LinkHub freely. If you run a **modified**
version as a web service for others, you must offer its users the source code
of that version.

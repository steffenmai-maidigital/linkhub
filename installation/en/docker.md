# Installing LinkHub with Docker

*Deutsche Fassung: [../docker.md](../docker.md)*

This guide applies to any machine with Docker: a Linux server, a mini PC or –
for trying it out – Docker Desktop on Mac and Windows. There is a separate
guide for Synology and QNAP: [synology.md](synology.md).

LinkHub runs in two containers built from the same image:

| Container | Purpose |
|---|---|
| `linkhub` | web interface on port **7280** |
| `linkhub-scheduler` | scheduled tasks: nightly backup |

## Requirements

- Docker 24 or newer with Docker Compose (`docker compose version` shows the version)
- at least 1 GB of free RAM and 2 GB of disk space
- internet access **only while building** the image (Composer downloads
  Laravel and other packages). LinkHub does not need the internet at runtime.
- for production: access to a domain controller (LDAP, preferably LDAPS)

## 1. Get the files

```bash
git clone https://github.com/steffenmai-maidigital/linkhub.git
cd linkhub
mkdir -p backups
```

Without Git: choose **Code → Download ZIP** on GitHub, unpack it, change into
the folder and create a folder called `backups` inside it. The nightly backups
are stored there.

## 2. Start it for a trial

Out of the box LinkHub runs in **demo mode**: without Active Directory, with
sample data and fixed demo accounts. The portal itself can be used without logging in; logging in is only needed for maintenance.

```bash
docker compose up -d --build
docker compose logs -f app
```

The first start builds the image and takes a few minutes. It is ready when the
log shows `resuming normal operations` (leave the log with **Ctrl+C**). Then
open <http://localhost:7280> or `http://<server-ip>:7280` in the browser.

Demo accounts (log in at the top right):

| Account | Password | Role |
|---|---|---|
| `admin` | `admin` | administrator – settings, audit log, backup |
| `manager` | `manager` | manager – maintains applications and structure |

## 3. Set it up for production

### 3.1 Adjust the Compose file

All settings are in `docker-compose.yml` under the `app` service. No `.env`
file is needed; the `scheduler` takes over the values automatically.

| Setting | What to enter |
|---|---|
| `DEMO_MODE` | `"false"` |
| `APP_URL` | the address LinkHub will be reached at, e.g. `https://links.firma.de` – without a trailing slash |
| `LDAP_HOST` | name of the domain controller, e.g. `dc01.example.local` |
| `LDAP_PORT` and `LDAP_SSL` | `636` and `"true"` for LDAPS (recommended) – or `389` with `LDAP_TLS: "true"` |
| `LDAP_BASE_DN` | search base, e.g. `DC=example,DC=local` |
| `LDAP_USERNAME`, `LDAP_PASSWORD` | a service account that can only **read** the directory |
| `AD_GROUP_ADMINS` | **name** of the administrators group, e.g. `LinkHub-Admins` (not a DN) |
| `AD_GROUP_MANAGERS` | **name** of the managers group, e.g. `LinkHub-Manager` |
| Port `"7280:80"` | put another port on the left if 7280 is already in use |

The full list of settings is in the
[README](../../README.en.md#settings-environment-variables).

Nobody needs to create `APP_KEY` by hand any more: LinkHub generates it on the
first start and stores it in the data volume as `/data/.app_key`.

### 3.2 Restart

```bash
docker compose up -d
```

If you tested in demo mode before and want to start with an empty database,
set `FRESH_DB: "1"` for **one** start and change it straight back to
`FRESH_DB: "0"` – otherwise the database is wiped on every start.

### 3.3 Set it up in the web interface

1. Log in at the top right with an account from the administrators group.
   User accounts are created automatically on first login; roles come from
   the AD groups on every login (direct memberships only).
2. Open **Settings**: company name, logo and colours, the two AD groups and
   backup retention.
3. **Structure:** create areas, departments and categories.
4. **Applications:** enter the applications with link, short description and
   optional manual link and assign them to areas or departments. Without an
   assignment an application only appears under "All".
5. Check filters and search in the portal (without logging in).

## 4. Set up HTTPS

Inside the container LinkHub only speaks HTTP. In production put a reverse proxy
with a certificate in front of it – for example nginx, Nginx Proxy Manager,
Caddy or the Synology reverse proxy. How to do this and what matters is in
[reverse-proxy.md](reverse-proxy.md).

Do not open port 7280 to the outside in your router or firewall; only the
reverse proxy should be reachable.

## 5. Checklist before going live

- [ ] `DEMO_MODE: "false"` and `APP_DEBUG: "false"`
- [ ] login with a real AD account works and the roles are correct
- [ ] LinkHub is only reachable over HTTPS, `APP_URL` starts with `https://`
- [ ] `TRUSTED_PROXIES` contains the reverse proxy's address (default: internal networks)
- [ ] after the first night there is a backup in the `backups` folder
- [ ] the `backups` folder and `docker-compose.yml` are also backed up elsewhere (e.g. to a second device)
- [ ] imprint and privacy notice are set under **Settings**, if required

## Trying it without Compose

For a quick look a single container is enough. All data is lost when it stops.

```bash
docker build -t linkhub:dev .
docker run --rm -p 7280:80 -e DEMO_MODE=true linkhub:dev
```

## Common problems

| Problem | Solution |
|---|---|
| The build fails | Usually the internet connection dropped while downloading packages. Simply run `docker compose up -d --build` again. |
| The container does not start, the log says "Downgrade abgelehnt" | The database comes from a newer version than the image. See [operations.md](operations.md#going-back-to-an-older-version). |
| "Server Error" in the browser | Set `APP_DEBUG: "true"` briefly, run `docker compose up -d`, read the error and make sure to set it back to `"false"`. |
| Page without styling (text only) | `APP_URL` does not match the address in the browser, see [reverse-proxy.md](reverse-proxy.md). |
| Login fails | With `LDAP_LOGGING: "true"` the LDAP messages appear in the log (`docker compose logs app`). Common causes: wrong base DN, service account locked, the domain controller's certificate is not trusted. |
| "Too many failed attempts for this user name" | After 10 wrong passwords the name is locked for 30 minutes. Wait – or clear the cache: `docker exec linkhub php artisan cache:clear`. |

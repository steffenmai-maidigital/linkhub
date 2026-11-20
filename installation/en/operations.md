# Running LinkHub: updates, backups, restore, emergencies

*Deutsche Fassung: [../betrieb.md](../betrieb.md)*

Applies to Docker in general and to Synology/QNAP. On the NAS via SSH, prefix
each command with `sudo`. The examples assume the Compose project is called
`linkhub` and lives in the folder `linkhub` – the volumes are then called
`linkhub_app-data` and so on (`docker volume ls` shows the actual names).

## What is where?

| Location | Content |
|---|---|
| volume `linkhub_app-data` (`/data`) | database `linkhub.sqlite`, key `.app_key`, backups taken before every update under `backups/` |
| volume `linkhub_app-storage` | sessions, cache, logs |
| volume `linkhub_app-uploads` | company logo |
| folder `./backups` | nightly backups with database, logo and app key; `manual/` for backups created by hand |
| `docker-compose.yml` | all settings, including AD credentials |

The Compose file contains credentials. Back it up, but do not keep it in a
folder that many people can read. The same applies to the `backups` folder:
every backup contains the app key.

## Update

**Docker:**

```bash
cd linkhub
git pull                     # or copy the new files over the folder
docker compose up -d --build
docker compose logs -f app
```

**Synology Container Manager:** save your own `docker-compose.yml` → stop the
project → extract the new files over the old ones → copy `docker-compose.yml`
back → **Action → Clean and rebuild**.

Before updating, check `CHANGELOG.md` under **Geändert** (changed) for
anything you need to do (e.g. a new folder or a new setting).

### What happens on start

On every start the app container runs `php artisan app:upgrade`:

| Step | Effect |
|---|---|
| Version check | The version stored in the database is compared with the image version. If the database is newer (**downgrade**), the container does not start – nothing is changed. |
| Backup | Before any change a copy of the database is stored in `/data/backups/` (the 10 newest are kept). |
| Migrations | New tables and columns are created. |
| Conversions | Version-specific data changes run exactly once. |
| Record | Version, build and time are then shown under **Help** and in the audit log. |

### Versions

Version numbers follow the scheme **YYMMDDNN**: date plus a running number for
that day (`26101701` = first version on 17 Oct 2026). Every build is called
`linkhub:latest`; released versions also carry their number. To list the
images:

```bash
docker image ls linkhub
```

### Going back to an older version

Because LinkHub refuses downgrades, going back also needs the database from
before:

1. Stop the containers: `docker compose down`
2. Find and restore the backup taken before the update:

   ```bash
   docker run --rm -v linkhub_app-data:/data alpine ls -1 /data/backups
   docker run --rm -v linkhub_app-data:/data alpine sh -c \
     'cp /data/backups/FILE.sqlite /data/linkhub.sqlite && rm -f /data/linkhub.sqlite-wal /data/linkhub.sqlite-shm'
   ```

3. In `docker-compose.yml` comment out `build: .` for **both** services
   (`# build: .`) and set the old number under `image:`, e.g.
   `image: linkhub:26101701`.
4. `docker compose up -d`

So do not delete old images too early (`docker image prune -a` removes them).

## Backups

- **Automatic:** every night at 1:30 the `linkhub-scheduler` container
  stores a ZIP with the database and uploads, plus a SHA-256 checksum, in
  `./backups`. The administrator sets how long they are kept under **Settings → Backup** (at least 4 days).
- **Right now, in the browser:** as administrator under **Settings →
  Create backup now**. These backups are stored in `./backups/manual` and are
  never deleted automatically.
- **Keep a copy elsewhere:** regularly copy the `backups` folder and
  `docker-compose.yml` to a second device, on Synology e.g. with
  **Hyper Backup**.

Every backup contains the **app key** next to the database and logo
(`database/.app_key`, a copy of `/data/.app_key`). Without it the service
account password and the two-factor secrets cannot be read after a restore on
new hardware. Whoever has a backup can decrypt them. Therefore:

- Only administrators may read the `backups` folder and all its copies.
  LinkHub creates the ZIP files without read access for others (`0640`); use
  `sudo` to check or copy them on the host.
- Backups only exist on the server; there is no download in the browser.
- If `APP_KEY` is set in `docker-compose.yml`, there is no `/data/.app_key`.
  The backup then contains no key, and `docker-compose.yml` belongs in the
  backup as well.

To check a backup:

```bash
cd backups && sudo sha256sum -c FILE.zip.sha256
```

## Restore

At the end LinkHub runs with the state of the backup: data, logo and the key
that keeps the service account password and two-factor readable.

Replace `FILE.zip` with the name of the backup (for manual backups:
`manual/FILE.zip`).

```bash
cd linkhub
docker compose stop

sudo docker run --rm -v linkhub_app-data:/data -v linkhub_app-uploads:/uploads \
  -v "$PWD/backups:/backups:ro" alpine sh -c "
  set -e; mkdir /tmp/r && cd /tmp/r && unzip -q /backups/FILE.zip
  rm -f /data/linkhub.sqlite /data/linkhub.sqlite-wal /data/linkhub.sqlite-shm
  cp database/linkhub.sqlite /data/linkhub.sqlite
  if [ -f database/.app_key ]; then cp database/.app_key /data/.app_key && chmod 600 /data/.app_key; fi
  if [ -d uploads ]; then cp -r uploads/. /uploads/; fi
  echo restored"

docker compose start
```

LinkHub fixes the file permissions itself on start. Older backups contain no
key yet; then the existing one stays in use. If such a backup comes from
another server, the service account password and two-factor secrets cannot be
read without that server's `/data/.app_key`.

**Moving to another server:** set up LinkHub there as described in the
installation guide (same `docker-compose.yml`), start it once and then run the
restore as above. It takes the app key along. Afterwards check `APP_URL` and –
if used – the addresses in the settings.

## Test system

`docker-compose.test.yml` starts a completely separate second instance on
port **7281** – with its own volumes, its own `backups-test` folder and its
own image `linkhub:test`. Production is not touched.

```bash
mkdir -p backups-test
docker compose -p linkhub-test -f docker-compose.test.yml up -d --build
```

Synology: create `docker-compose.test.yml` as a separate project
`linkhub-test` in the same folder.

The test system shows a red **TESTSYSTEM** banner at the top. The production
backup folder is mounted read-only there: under **Settings → Test system** the
administrator restores the latest production state with one click; the
checksum is verified first. Afterwards the production accounts apply: LinkHub
signs out, and you sign in with a production account. The test system only
reads the database from the backup and does not take over the production app
key. Encrypted production data (service account password, two-factor) can
therefore not be read in the test system.

## Emergencies

### No administrator can get in

An own admin account always works, even if the directory is down:

```bash
docker exec linkhub php artisan linkhub:admin-password
```

The command creates the account `admin` or resets its password (another name:
`linkhub:admin-password name`). The password it prints is an initial password:
it must be changed under **My account** at sign-in, and running sessions of the
account end. If the phone with the authenticator app is gone, `--disable-2fa`
also resets the two-factor sign-in:

```bash
docker exec linkhub php artisan linkhub:admin-password admin --disable-2fa
```

The command refuses directory accounts; their password is managed by the
directory.

If administrators from the directory cannot get in: the administrators group
is set under **Settings → 3**, otherwise in `AD_GROUP_ADMINS`. A group name
becomes the full DN when saving; if the name exists in several OUs, LinkHub asks
for the DN. Check that the account is a member of exactly that group (with
Active Directory nested groups count too). Directory messages are in the log
(`docker compose logs -f app`).

### A user name is locked

After 10 wrong passwords a user name is locked for 30 minutes. To lift it
immediately:

```bash
docker exec linkhub php artisan cache:clear
```

### Page without styling or "too many redirects"

See [reverse-proxy.md](reverse-proxy.md#common-mistakes).

### Troubleshooting

- Container log: `docker compose logs -f app` (Synology: **Project → Log**)
- Laravel log: `docker exec linkhub tail -n 100 storage/logs/laravel.log`
- For a detailed error in the browser set `APP_DEBUG: "true"` briefly, restart
  and make **sure** to set it back to `"false"`.

### Resetting everything

`FRESH_DB: "1"` in `docker-compose.yml` deletes the database on the next
start. Set it back to `"0"` right away – otherwise it happens on every start.

## Recurring tasks

| When | What |
|---|---|
| monthly | look into the `backups` folder (is there a new backup every night?) and under **Help** |
| before every update | **Create backup now**, read `CHANGELOG.md`, try the update in the test system first |
| quarterly | check the links in the portal: are there applications that no longer exist? |

## Useful commands

Each prefixed with `docker exec linkhub`:

| Command | Purpose |
|---|---|
| `php artisan app:upgrade` | run the version check and migrations by hand |
| `php artisan schedule:list` | show scheduled tasks and their next run |
| `php artisan cache:clear` | clear the cache (also lifts login locks) |
| `php artisan linkhub:admin-password [name] [--disable-2fa]` | emergency access: own admin account with an initial password, optionally without two-factor |

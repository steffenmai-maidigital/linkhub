# 🔗 LinkHub — Community Edition

**Freies, selbst gehostetes Link- und Applikationsportal mit AD-Login.**
LinkHub ist die zentrale Startseite für alle Web-Anwendungen eines
Unternehmens: Mitarbeiter finden ihre Anwendungen samt Kurzbeschreibung und
Handbuch-Link, gefiltert nach Bereichen und Abteilungen — ohne Anmeldung.
Gepflegt wird nach dem Login durch Manager und Administratoren. Die
*Community Edition* ist die vollständige Open-Source-Fassung (AGPL-3.0); alle
Daten liegen in einer SQLite-Datei auf dem eigenen Server.

*English version: [README.en.md](README.en.md)*

---

## Warum?

Lesezeichen-Listen im Intranet veralten, und neue Kollegen wissen nicht, wo
welche Anwendung liegt. LinkHub macht daraus ein gepflegtes Portal:

- **Portal ohne Anmeldung**, nur lesend — es zeigt ausschließlich gepflegte
  Links und Beschreibungen, keine personenbezogenen Daten.
- **Pflege mit eigenem Konto oder Windows-Konto**: Administratoren legen
  Konten in der Oberfläche an; zusätzlich kann ein Verzeichnis (Active
  Directory, Samba AD, OpenLDAP, Univention) angebunden werden — Rollen
  kommen dann bei jedem Login aus den Gruppen.
- **Nachvollziehbar**: jede Änderung im Audit-Log mit SHA-256-Hash-Kette.
- **Gesichert**: nächtliches Backup mit Prüfsummen, dazu ein Testsystem mit
  Übernahme des Produktionsstands per Klick.

## Funktionen

| Bereich | Was geht |
|---|---|
| Portal | Kacheln mit Monogramm, Kurzbeschreibung, „Öffnen“ und optionalem Handbuch-Link; Suche; hell und dunkel |
| Filter | Bereiche als Reiter, darunter die Abteilungen des Bereichs; „Alle“ zeigt den ganzen Katalog |
| Sichtbarkeit | Anwendungen gehören zu Bereichen und/oder Abteilungen; ohne Zuordnung nur unter „Alle“ |
| Kategorien | Abschnitte auf der Startseite (z. B. „Kommunikation“); ohne Kategorie unter „Weitere“ |
| Pflege | Anwendungen per Formular; Bereiche, Abteilungen und Kategorien mit Inline-Bearbeitung |
| Sprachen | Oberfläche Deutsch/Englisch mit Umschalter; optionale englische Namen und Beschreibungen für alle Inhalte |
| Gestaltung | Firmenname (ersetzt „LinkHub“ im Header), Logo, Header-, Akzent- und Footer-Farbe, Footer-Text; Glas-Design, hell und dunkel |
| Impressum und Datenschutz | einzeln einschaltbar, als eigener Text oder Link auf die Firmen-Homepage; ohne Anmeldung erreichbar, mit Vorlage für die Datenschutzhinweise |
| Audit | jede Eingabe, Änderung und Löschung sowie An- und Abmeldungen (spatie/activitylog) mit Hash-Kette und Prüf-Knopf |
| Backup | nächtlich um 01:30 Uhr, Aufbewahrung einstellbar (mindestens 4 Tage); „Jetzt sichern“ mit Prüfsummendatei, wird nie automatisch gelöscht |
| Testsystem | zweite, getrennte Instanz (Port 7281) mit TESTSYSTEM-Banner und Einspielen des letzten Produktionsstands |
| Hilfe | Version, Lizenz, Handbücher und Changelog direkt in der Anwendung (Footer → **Hilfe**) |
| Anmeldung | eigene Konten (Menü **Benutzer**) immer; Verzeichnis optional mit „Verbindung testen“; Gruppen als DN oder Name, verschachtelte AD-Gruppen; Notzugang per `php artisan linkhub:admin-password` |
| Zwei-Faktor | TOTP mit Authenticator-App; je Gruppe Pflicht, wählbar oder aus, Verzeichnisgruppen mit Pflicht, Ausnahme je Benutzer; Wiederherstellungscodes; Zurücksetzen durch Administratoren |
| Fuchs-Helfer | **Rua, der Fuchs** (Wikinger-Fuchs aus einem norwegischen Fjorddorf, von den Iren „der Rote“ genannt) begrüßt angemeldete Benutzer, gibt Tipps passend zu Rolle und Seite, wirbt auf der Anmeldeseite für freie Software; nicht auf dem öffentlichen Portal; zentral und je Person abschaltbar |
| Betrieb | Docker, SQLite (WAL), sicheres Update mit Downgrade-Schutz, Demo-Modus ohne Verzeichnis |
| Sicherheit | Sicherheits-Header (CSP, HSTS), Bremse gegen Passwort-Raten je Adresse und je Benutzername, Proxy-Angaben nur aus internen Netzen |

## Rollen

| Rolle | Darf |
|---|---|
| **Besucher** | Portal ansehen, suchen, filtern — ohne Anmeldung |
| **Manager** | Anwendungen, Bereiche, Abteilungen und Kategorien pflegen |
| **Administrator** | zusätzlich Einstellungen, Audit-Log, Backup und Testsystem |

Manager und Administratoren sind entweder **eigene Konten** (Menü
**Benutzer**, Rechte per Haken) oder **Verzeichniskonten**: Dann bestimmt je
eine Gruppe die Rolle (**Einstellungen → 3 · Rollen aus Gruppen**, Startwerte
aus `AD_GROUP_MANAGERS` und `AD_GROUP_ADMINS`). Der Abgleich läuft bei jedem
Login; bei Active Directory werden auch verschachtelte Gruppen aufgelöst.
Verzeichniskonten entstehen automatisch beim ersten Login.

## Anleitungen

- [Installation mit Docker](installation/docker.md) — Linux-Server, Mini-PC, Docker Desktop, Reverse Proxy
- [Installation auf Synology (Container Manager)](installation/synology.md)
- [Betrieb](installation/betrieb.md) — Updates, Sicherung, Wiederherstellung, Testsystem
- Handbücher: [Benutzer](docs/USER-GUIDE.md) · [Administrator](docs/ADMIN-GUIDE.md) · [Architektur](docs/ARCHITECTURE.md) · [Sicherheit](docs/SECURITY.md) · [Überblick](docs/DOCUMENTATION.md)

## Schnellstart

Voraussetzung ist Docker mit Compose.

```bash
git clone https://github.com/steffenmai-maidigital/linkhub.git
cd linkhub
mkdir -p backups
docker compose up -d --build
```

Danach ist LinkHub unter <http://localhost:7280> erreichbar. Im
Auslieferungszustand läuft der **Demo-Modus** mit Beispieldaten; Anmeldung
oben rechts mit `admin` / `admin` oder `manager` / `manager`.

### Normalbetrieb

1. In `docker-compose.yml` `DEMO_MODE: "false"` setzen. Eine `.env` ist nicht
   nötig. Beim ersten Start legt LinkHub den Administrator `admin` als eigenes
   Konto an; das Startpasswort steht im Container-Protokoll
   (`docker compose logs app`) oder wird vorher als `ADMIN_PASSWORD` gesetzt.
2. Als `admin` anmelden → **Benutzer**: eigenes Passwort vergeben, weitere
   Konten anlegen — oder unter **Einstellungen → 2 · Anmeldung** das
   Verzeichnis eintragen, speichern und „Verbindung testen“; die Rollen
   kommen dann aus den Gruppen unter **Einstellungen → 3**.
3. **Einstellungen**: Firma und Erscheinungsbild, Backup, Impressum und
   Datenschutz.
4. **Struktur** (Bereiche, Abteilungen, Kategorien) anlegen, dann die
   **Applikationen**.

Niemand ausgesperrt: `docker exec linkhub php artisan linkhub:admin-password`
legt jederzeit ein eigenes Admin-Konto an oder setzt dessen Passwort neu.

Den Schlüssel `APP_KEY` erzeugt LinkHub beim ersten Start selbst und legt ihn
unter `/data/.app_key` ab. Schritt für Schritt: [installation/docker.md](installation/docker.md).

Hinter einem Reverse Proxy `APP_URL` auf die öffentliche Adresse setzen
(siehe [installation/reverse-proxy.md](installation/reverse-proxy.md)).

### Einstellungen (Umgebungsvariablen)

| Variable | Standard | Bedeutung |
|---|---|---|
| `DEMO_MODE` | true (Compose) | Demo-Konten und Beispieldaten, kein Verzeichnis |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | admin / *(leer = zufällig, steht im Protokoll)* | erster Administrator als eigenes Konto, nur beim allerersten Start |
| `TEST_MODE` | false | kennzeichnet die Instanz als „TESTSYSTEM“ |
| `APP_NAME` | LinkHub | Name der Anwendung (der Firmenname aus den Einstellungen ersetzt ihn im Header) |
| `APP_URL` | http://localhost:7280 | öffentliche Adresse |
| `APP_KEY` | *(leer = beim ersten Start erzeugt, liegt in `/data/.app_key`)* | nur setzen, wenn ein bestimmter Schlüssel gelten soll |
| `TRUSTED_PROXIES` | interne Netze | Reverse Proxys, deren `X-Forwarded-*`-Angaben geglaubt werden; `*` = allen (nicht empfohlen) |
| `SOURCE_URL` | *(leer)* | Link zum Quellcode in der Fußzeile; bei eigenen Änderungen auf das eigene Repo setzen (AGPL) |
| `GITHUB_URL`, `PROJECT_URL` | GitHub-Projekt, www.mai-digital.de | Links unter **Hilfe** und in der Fußzeile |
| `APP_DEBUG` | false | zeigt Fehlermeldungen im Browser — nur kurzzeitig zur Fehlersuche |
| `DB_DATABASE` | /data/linkhub.sqlite | Datenbankdatei im Volume `app-data` |
| `BACKUP_PATH` | /backups | Zielordner der Sicherungen (im Compose `./backups`) |
| `LDAP_HOST`, `LDAP_PORT`, `LDAP_SSL`, `LDAP_TLS` | — | optionale Startwerte für das Verzeichnis (sonst alles unter **Einstellungen → Anmeldung**); ein gesetzter Host schaltet die Verzeichnis-Anmeldung ein |
| `LDAP_BASE_DN`, `LDAP_USERNAME`, `LDAP_PASSWORD` | — | Suchbasis und Servicekonto (Startwerte) |
| `LDAP_ENABLED`, `LDAP_USER_ATTRIBUTE`, `LDAP_USER_FILTER`, `LDAP_BIND_TEMPLATE`, `LDAP_VERIFY_CERT`, `LDAP_TIMEOUT` | — | weitere Startwerte, siehe `.env.example` |
| `AD_GROUP_ADMINS`, `AD_GROUP_MANAGERS` | LinkHub-Admins, LinkHub-Manager | Startwerte für die Rollen (danach in den Einstellungen) |
| `FRESH_DB` | 0 | `1` löscht beim Start die Datenbank. Danach sofort wieder auf `0` stellen |

### Daten und Sicherung

- `app-data` (Volume): Datenbank `linkhub.sqlite`, Schlüssel `.app_key` und die
  Sicherungen vor jedem Update unter `/data/backups/` (die 10 neuesten bleiben)
- `app-storage` (Volume): Sitzungen, Protokolle, Zwischenspeicher
- `app-uploads` (Volume): Firmenlogo
- `./backups` (Ordner, vorher anlegen): nächtliche Sicherungen als ZIP mit
  Prüfsumme, manuelle Sicherungen unter `./backups/manual`

Wiederherstellen und Umziehen: siehe [installation/betrieb.md](installation/betrieb.md).

### Testsystem

`docker-compose.test.yml` startet eine vollständig getrennte zweite Instanz
auf Port 7281 (eigene Volumes, eigener Ordner `backups-test`, eigenes Abbild
`linkhub:test`) mit unübersehbarem TESTSYSTEM-Banner. Der Backup-Ordner der
Produktion ist dort nur lesend eingebunden; unter **Einstellungen →
Testsystem** lässt sich der letzte Produktionsstand per Klick einspielen.

### Editionen und Lizenz

| Edition | Was |
|---|---|
| **Community Edition** | die Open-Source-Fassung. Vollständig, ohne Einschränkungen und ohne Ablaufdatum. |
| **Supported Edition** | dieselbe Software mit einer gepflegten Support-Lizenz (Standard oder Enterprise). |

Die Lizenz ist **rein informativ und hat keine funktionalen Auswirkungen**.
Admins tragen sie unter **Einstellungen → Lizenz** ein (Lizenznehmer und
20-stelliger Schlüssel), angezeigt wird sie unter **Hilfe**.

| Stellen | Bedeutung | Beispiel |
|---|---|---|
| 1 | `1` = Standard-Lizenz, `2` = Enterprise-Lizenz | `1` |
| 2–9 | Kundennummer (8 Zeichen, Buchstaben und Zahlen) | `MUSTER01` |
| 10–15 | Support-Ende als `JJMMTT` | `270731` |
| 16–20 | Lizenzanzahl (5 Ziffern, `99999` = unlimitiert) | `00500` |

## Versionen und Docker-Abbilder

Versionsnummern folgen dem Schema **JJMMTTNN**: Datum plus laufende Nummer
des Tages. `26101701` ist die erste Version am 17.10.2026. Ältere Stände
trugen Nummern nach MAJOR.MINOR.PATCH (bis 2.1.3, siehe [CHANGELOG](CHANGELOG.md)).

Jeder Bau erzeugt das Docker-Abbild **`linkhub:latest`**. Eine veröffentlichte
Version bekommt zusätzlich ihre Nummer als Tag, z. B. **`linkhub:26101701`**.
Für jede Version baut GitHub das Abbild außerdem für Intel/AMD und ARM und legt
es als `ghcr.io/steffenmai-maidigital/linkhub:<version>` und `:latest` in die
Paketablage.

Neue Versionen veröffentlichen die Betreuer; was sich in jeder Version ändert,
steht im [CHANGELOG](CHANGELOG.md).

## Entwicklung

```bash
docker build -t linkhub:dev .
docker run --rm -p 7280:80 -e DEMO_MODE=true -e APP_DEBUG=true linkhub:dev
```

Ohne Docker:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/data/linkhub.sqlite
DB_DATABASE=$PWD/database/data/linkhub.sqlite DEMO_MODE=true php artisan migrate --seed
DB_DATABASE=$PWD/database/data/linkhub.sqlite DEMO_MODE=true php artisan serve
```

Aufbau:

```
app/Models            Application, Area, Department, Category, Setting, User, ActivityLog
app/Support           Directory (AD/LDAP, Rollen aus Gruppen bei jedem Login), Mfa, Fox, LegalPages, ProductInfo, ErrorPage
app/Services          BackupService, LicenseService
app/Http/Controllers  Portal, Applikationen, Struktur, Einstellungen, Backup, Testsystem, Docs, Support, Anmeldung
resources/views       Blade-Oberfläche, eine Stylesheet-Datei unter public/css/app.css
lang/en.json          englische Übersetzung (deutsche Texte sind die Schlüssel)
docs/                 Handbücher (auch in der Anwendung unter „Hilfe“)
installation/         Anleitungen: Docker, Synology, Reverse Proxy, Betrieb (en/ = englisch)
```

Ohne Build-Schritt: kein Node und kein npm.

### Übersetzen

Alle Oberflächentexte sind auf Deutsch geschrieben und dienen als Schlüssel.
Die englischen Texte stehen in `lang/en.json`. Neue Texte bitte gleich in
`lang/en.json` ergänzen und Platzhalter wie `:name` übernehmen; vor jeder
Version prüfen die Betreuer, ob alles übersetzt ist. Fehlende Texte erscheinen auf Deutsch.

## Mitmachen

Siehe [CONTRIBUTING.md](CONTRIBUTING.md). Sicherheitslücken bitte nicht
öffentlich melden, siehe [SECURITY.md](SECURITY.md).

## Lizenz

[GNU Affero General Public License v3.0](LICENSE) (AGPL-3.0-or-later).

Ihr dürft LinkHub frei nutzen, verändern und weitergeben. Wer eine
**veränderte** Fassung als Webdienst für andere betreibt, muss deren Nutzern den
Quellcode dieser Fassung anbieten.

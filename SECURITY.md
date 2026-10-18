# Sicherheit / Security

*Erst Deutsch, dann Englisch — German first, English below.*

## Deutsch

### Sicherheitslücke melden

Bitte melde Sicherheitslücken **nicht** öffentlich als Issue, sondern
vertraulich an die Betreuer des Projekts, über die private
Sicherheitsmeldung von GitHub (*Security → Report a vulnerability*). Wir
melden uns so schnell wie möglich und sagen Bescheid, sobald eine
Korrektur veröffentlicht ist.

Hilfreich sind: betroffene Version (Anmeldeseite oder **Support & Lizenz**),
eine kurze Beschreibung, Schritte zum Nachstellen und, falls bekannt,
mögliche Auswirkungen.

### Unterstützte Versionen

Korrekturen gibt es für die **jeweils neueste Version** (Schema JJMMTTNN,
siehe [README](README.md#versionen-und-docker-abbilder)).

### Eingebaute Schutzmaßnahmen

- **Portal nur lesend**, zeigt ausschließlich gepflegte Inhalte; jede
  schreibende Aktion erfordert eine Anmeldung.
- **Anmeldung nur über Active Directory**; LinkHub speichert keine eigenen
  Passwörter (Ausnahme: lokale Demo-Konten im Demo-Modus).
- **Rollen aus AD-Gruppen**, bei jedem Login neu abgeglichen; entzogene
  Gruppen wirken spätestens beim nächsten Login.
- **Bremse gegen Passwort-Raten**: 10 Anmeldeversuche je Minute; neue
  Sitzung bei jedem Login, CSRF-Schutz aller Formulare.
- **Audit-Log** mit SHA-256-Hash-Kette und Prüf-Knopf.
- **Uploads**: nur PNG, JPG, SVG und WebP bis 2 MB, eigener Dateiname.
- **Handbücher in der Anwendung**: Markdown wird ohne HTML und ohne
  unsichere Links dargestellt.
- **Backups** mit SHA-256-Prüfsumme; das Testsystem prüft sie vor dem
  Einspielen.

Details: [docs/SECURITY.md](docs/SECURITY.md).

### Hinweise für den Betrieb

- Nur über HTTPS betreiben (Reverse Proxy mit Zertifikat), Port `7280`
  nicht direkt freigeben.
- `TRUSTED_PROXIES` auf die IP des Reverse Proxys einschränken.
- LDAPS zum Domain Controller verwenden; das Servicekonto braucht nur
  Leserechte.
- Den Demo-Modus nie im Produktivbetrieb verwenden (`admin` / `admin`).
- `APP_DEBUG` im Betrieb immer auf `false` lassen.
- `docker-compose.yml` enthält `APP_KEY` und den AD-Zugang: Dateirechte
  restriktiv halten.
- Regelmäßig auf die neueste Version aktualisieren.

## English

### Reporting a vulnerability

Please do **not** report security vulnerabilities as a public issue. Report
them privately to the project maintainers via GitHub's private vulnerability
reporting (*Security → Report a vulnerability*). We will get back to you as
soon as possible and let you know once a fix has been released.

Helpful details: the affected version (login page or **Support & licence**),
a short description, steps to reproduce and, if known, the possible impact.

### Supported versions

Fixes are provided for the **latest version** (scheme YYMMDDNN, see the
[README](README.en.md#versions-and-docker-images)).

### Built-in protections

- **Read-only portal** showing only maintained content; every write action
  requires login.
- **Login only via Active Directory**; LinkHub stores no passwords of its own
  (except local demo accounts in demo mode).
- **Roles from AD groups**, re-synchronised on every login.
- **Password-guessing brake**: 10 login attempts per minute; new session on
  every login, CSRF protection on all forms.
- **Audit log** with a SHA-256 hash chain and a verify button.
- **Uploads**: only PNG, JPG, SVG and WebP up to 2 MB, generated file name.
- **In-app manuals**: Markdown is rendered without HTML and without unsafe
  links.
- **Backups** with SHA-256 checksums; the test system verifies them before
  restoring.

Details (German): [docs/SECURITY.md](docs/SECURITY.md).

### Operating securely

- Only run it over HTTPS behind a reverse proxy; do not expose port `7280`
  directly.
- Restrict `TRUSTED_PROXIES` to the reverse proxy's IP.
- Use LDAPS; the service account only needs read access.
- Never use demo mode in production (`admin` / `admin`).
- Always keep `APP_DEBUG` set to `false` in production.
- `docker-compose.yml` contains `APP_KEY` and AD credentials: keep file
  permissions tight.
- Update to the latest version regularly.

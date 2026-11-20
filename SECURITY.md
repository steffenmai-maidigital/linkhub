# Sicherheit / Security

*Erst Deutsch, dann Englisch — German first, English below.*

## Deutsch

### Sicherheitslücke melden

Bitte melden Sie Sicherheitslücken **nicht** öffentlich als Issue, sondern
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
- **Eigene Konten** mit bcrypt-Passwort, optional zusätzlich ein Verzeichnis
  (Active Directory, LDAP); Verzeichnis-Passwörter speichert LinkHub nicht.
- **Startpasswörter** (erster Administrator, neue Konten, zurückgesetzte
  Passwörter, Notzugang) müssen bei der Anmeldung geändert werden; bis dahin
  ist nur **Mein Konto** erreichbar.
- **Passwortwechsel beendet die anderen Sitzungen** des Kontos, auch nach
  einem Zurücksetzen durch einen Administrator.
- **Rollen aus Verzeichnisgruppen**, bei jedem Login neu abgeglichen; ein
  Gruppenname wird beim Speichern zum eindeutigen DN, doppelte Namen lehnt
  LinkHub ab.
- **Bremse gegen Passwort-Raten**: 10 Anmeldeversuche je Minute; neue
  Sitzung bei jedem Login, CSRF-Schutz aller Formulare.
- **Audit-Log** mit SHA-256-Hash-Kette und Prüf-Knopf.
- **Uploads**: nur PNG, JPG, SVG und WebP bis 2 MB, eigener Dateiname.
- **Handbücher in der Anwendung**: Markdown wird ohne HTML und ohne
  unsichere Links dargestellt.
- **Backups** mit SHA-256-Prüfsumme; das Testsystem prüft sie vor dem
  Einspielen. Sie enthalten den App-Schlüssel, damit verschlüsselte Angaben
  nach einer Wiederherstellung lesbar sind, sind nicht für alle lesbar
  (`0640`) und lassen sich nicht im Browser herunterladen.

Details: [docs/SECURITY.md](docs/SECURITY.md).

### Hinweise für den Betrieb

- Nur über HTTPS betreiben (Reverse Proxy mit Zertifikat), Port `7280`
  nicht direkt freigeben.
- **`TRUSTED_PROXIES`** steht ab Werk auf allen internen Netzen (`10.0.0.0/8`,
  `172.16.0.0/12`, `192.168.0.0/16`), damit ein Reverse Proxy ohne weitere
  Einstellung funktioniert. Bekannte Folge: Jedes Gerät in diesen Netzen kann
  mit einer gefälschten Kopfzeile `X-Forwarded-For` eine andere Adresse
  vortäuschen; die Anmeldebremse je Adresse greift dann nicht, die Sperre je
  Benutzername schon. Wer das vermeiden will, trägt nur die IP des Reverse
  Proxys ein.
- LDAPS zum Domain Controller verwenden; das Servicekonto braucht nur
  Leserechte.
- Den Demo-Modus nie im Produktivbetrieb verwenden (`admin` / `admin`).
- `APP_DEBUG` im Betrieb immer auf `false` lassen.
- `docker-compose.yml` enthält `APP_KEY` und den AD-Zugang: Dateirechte
  restriktiv halten.
- Den Ordner `backups` und alle Kopien dürfen nur Administratoren lesen: Wer
  eine Sicherung hat, kann mit dem enthaltenen App-Schlüssel das
  Servicekonto-Passwort und die Zwei-Faktor-Geheimnisse entschlüsseln.
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
- **Own accounts** with bcrypt passwords, optionally a directory as well
  (Active Directory, LDAP); LinkHub does not store directory passwords.
- **Initial passwords** (first administrator, new accounts, reset passwords,
  emergency access) must be changed at sign-in; until then only **My
  account** is available.
- **A password change ends the other sessions** of the account, also after a
  reset by an administrator.
- **Roles from directory groups**, re-synchronised on every login; a group
  name becomes its unambiguous DN when saving, duplicate names are refused.
- **Password-guessing brake**: 10 login attempts per minute; new session on
  every login, CSRF protection on all forms.
- **Audit log** with a SHA-256 hash chain and a verify button.
- **Uploads**: only PNG, JPG, SVG and WebP up to 2 MB, generated file name.
- **In-app manuals**: Markdown is rendered without HTML and without unsafe
  links.
- **Backups** with SHA-256 checksums; the test system verifies them before
  restoring. They contain the app key so that encrypted data stays readable
  after a restore, are not world-readable (`0640`) and cannot be downloaded
  in the browser.

Details (German): [docs/SECURITY.md](docs/SECURITY.md).

### Operating securely

- Only run it over HTTPS behind a reverse proxy; do not expose port `7280`
  directly.
- **`TRUSTED_PROXIES`** covers all internal networks by default (`10.0.0.0/8`,
  `172.16.0.0/12`, `192.168.0.0/16`) so that a reverse proxy works without
  further settings. Known consequence: any device in these networks can fake
  another address with a forged `X-Forwarded-For` header; the sign-in throttle
  per address then does not apply, the lock per user name still does. To
  avoid this, enter only the reverse proxy's IP.
- Use LDAPS; the service account only needs read access.
- Never use demo mode in production (`admin` / `admin`).
- Always keep `APP_DEBUG` set to `false` in production.
- `docker-compose.yml` contains `APP_KEY` and AD credentials: keep file
  permissions tight.
- Only administrators may read the `backups` folder and all copies: whoever
  has a backup can decrypt the service account password and the two-factor
  secrets with the app key inside.
- Update to the latest version regularly.

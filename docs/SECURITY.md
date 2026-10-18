# Sicherheitsarchitektur — LinkHub

> Wird bei jedem Release mitgepflegt.

## Authentifizierung und Rollen
- Das **Portal ist bewusst öffentlich** (nur lesend): es zeigt
  ausschließlich gepflegte Links, Beschreibungen und Kategorien — keine
  personenbezogenen oder vertraulichen Daten. Jede schreibende Aktion
  erfordert eine Anmeldung.
- Anmeldung ausschließlich über **Active Directory (LDAP)** mit
  Service-Konto (LdapRecord); es werden keine eigenen Passwörter
  gespeichert (Ausnahme: lokale Demo-Konten im Demo-Modus).
- Rollen werden bei **jedem Login** aus den AD-Gruppen synchronisiert
  (`ad.group_admins`, `ad.group_managers`); entzogene Gruppen wirken
  spätestens beim nächsten Login. Geprüft werden direkte Mitgliedschaften
  (memberOf), verschachtelte Gruppen werden nicht aufgelöst.
- Berechtigungsmodell: **Administrator** (alles, inkl. Einstellungen,
  Audit-Log, Backup, Testsystem-Restore) und **Manager** (Pflege von
  Applikationen und Struktur). Durchgesetzt über Middleware
  (`EnsureAdmin`, `EnsureManager`) an den Routen.
- Anmeldeversuche sind gedrosselt (10/Minute); Sitzungen werden beim
  Login regeneriert (Session-Fixation-Schutz), CSRF-Schutz auf allen
  Formularen.

## Nachvollziehbarkeit (Audit)
- Alle Pflege-Aktionen (Applikationen, Struktur, Einstellungen) sowie An-
  und Abmeldungen werden mit Benutzer, Zeitpunkt und Feldwerten
  protokolliert (spatie/activitylog).
- **Hash-Kette:** Jeder Audit-Eintrag trägt einen SHA-256-Hash über den
  Vorgänger-Hash plus den eigenen Inhalt. Nachträgliche Änderung oder
  Entfernung einzelner Einträge bricht die Kette und ist über den Button
  „Hash-Kette prüfen" nachweisbar; das Prüfergebnis wird selbst
  protokolliert.

## Datensicherung
- Nächtliches Backup (01:30 Uhr) als ZIP (Datenbank per `VACUUM INTO`
  konsistent gezogen, plus Logo-Upload) mit **SHA-256-Prüfsummendatei**
  in den gemappten Ordner `backups/`.
- Aufbewahrung konfigurierbar mit harter Untergrenze von 4 Tagen;
  manuelle Backups unter `backups/manual` werden nie automatisch
  gelöscht.
- Testsystem-Restore verifiziert die Prüfsumme vor dem Einspielen.

## Plattform
- SQLite im WAL-Modus in einem eigenen Docker-Volume; Uploads getrennt im
  Storage-Volume; der Backup-Ordner ist der einzige Bind-Mount.
- Logo-Uploads werden validiert (Bildtypen PNG/JPG/SVG/WebP, max. 2 MB)
  und mit generiertem Dateinamen unter `public/uploads` abgelegt (nie mit
  Original-Dateinamen).
- In-App-Doku rendert Markdown mit `html_input = escape` und ohne
  unsichere Links.
- Betrieb hinter Reverse Proxy vorgesehen; Proxy-Angaben (`X-Forwarded-*`)
  werden standardmäßig nur aus internen Netzen geglaubt (`TRUSTED_PROXIES`).

## Sicherheits-Header
- Jede Antwort trägt eine strenge **Content-Security-Policy** (nur eigener
  Server), `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`,
  `Referrer-Policy: same-origin` und eine `Permissions-Policy` ohne Kamera,
  Mikrofon und Standort. Hinter HTTPS zusätzlich **HSTS** und Cookies nur mit
  `Secure` (Middleware `SecurityHeaders`).
- Zusätzlich zur Drosselung je Adresse: nach 10 Fehlversuchen ist ein
  Benutzername 30 Minuten gesperrt.
- Der Schlüssel `APP_KEY` liegt im Datenvolume (`/data/.app_key`, Rechte 600).

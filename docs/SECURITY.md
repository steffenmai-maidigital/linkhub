# Sicherheitsarchitektur — LinkHub

> Wird bei jedem Release mitgepflegt.

## Authentifizierung und Rollen
- Das **Portal ist bewusst öffentlich** (nur lesend): es zeigt
  ausschließlich gepflegte Links, Beschreibungen und Kategorien — keine
  personenbezogenen oder vertraulichen Daten. Jede schreibende Aktion
  erfordert eine Anmeldung.
- **Eigene Konten** gibt es immer: Passwörter liegen nur als bcrypt-Hash
  in der Datenbank. Der erste Administrator bekommt ein zufälliges
  Startpasswort (Container-Protokoll) oder `ADMIN_PASSWORD`; es gibt kein
  Standardpasswort. Notzugang: `php artisan linkhub:admin-password`
  (`--disable-2fa` setzt zusätzlich den zweiten Faktor zurück).
- **Startpasswörter** (erster Administrator mit Zufallspasswort, neue Konten,
  „Passwort zurücksetzen“, Passwort im Bearbeiten-Formular, Demo-Konten nach
  dem Wechsel in den Normalbetrieb, Notzugang) muss die Person ändern, bevor
  sie etwas anderes als **Mein Konto** und das Abmelden erreicht — auch keine
  Hilfe (Spalte `users.must_change_password`, Middleware
  `EnsurePasswordChanged`). Nicht für Verzeichniskonten, nicht im Demo-Modus.
- **Passwortwechsel beendet die anderen Sitzungen:** Laravels
  `AuthenticateSession` merkt sich in jeder Sitzung einen Fingerabdruck des
  Passwort-Hashes (HMAC mit `APP_KEY`). Ändert sich das Passwort (selbst,
  durch einen Administrator oder den Notzugang), enden alle Sitzungen mit dem
  alten Stand beim nächsten Aufruf; beim eigenen Wechsel bleibt die laufende
  Sitzung.
- **Verzeichnis** (Active Directory, Samba AD, OpenLDAP, Univention) ist
  optional: nur LDAP-Bind, LinkHub speichert keine Verzeichnis-Passwörter.
  Das Servicekonto liest nur; sein Passwort liegt mit `APP_KEY`
  verschlüsselt in den Einstellungen. Eingaben werden mit `ldap_escape`
  maskiert, das Zertifikat des Servers wird geprüft (eigene CA möglich).
  Verzeichniskonten werden über `objectGUID` bzw. `entryUUID`
  wiedererkannt; ein neu angelegtes Konto mit altem Namen übernimmt nie
  Rechte oder zweiten Faktor des alten.
- Rollen von Verzeichniskonten kommen bei **jeder Anmeldung** aus den
  Gruppen (`ad_group_admins`, `ad_group_managers`, als DN oder Name; bei
  AD auch verschachtelt). Ein Name wird beim Speichern zum DN
  (`Directory::resolveGroups()`); gibt es ihn in mehreren OUs, lehnt LinkHub
  ihn ab, damit eine gleichnamige Gruppe woanders keine Rechte und keine
  Zwei-Faktor-Pflicht verschiebt. Gilt auch für die Verzeichnisgruppen mit
  Zwei-Faktor-Pflicht. Entzogene Gruppen wirken ab der nächsten
  Anmeldung. Rechte eigener Konten wirken sofort.
- **Zwei-Faktor (TOTP)** je Gruppe als Pflicht, wählbar oder aus;
  Geheimnis und Wiederherstellungscodes verschlüsselt (Codes nur als
  Hash), Schutz gegen Wiederverwendung eines Codes, 5 falsche Codes →
  5 Minuten Pause. Ist der zweite Faktor Pflicht und noch nicht
  eingerichtet, ist nur die Konto-Seite erreichbar. Die Stufe prüft die
  Middleware `EnsureMfa` bei jeder Anfrage neu (strengere aus Anmeldung und
  aktueller Richtlinie).
- Berechtigungsmodell: **Administrator** (alles, inkl. Benutzer,
  Einstellungen, Audit-Log, Backup, Testsystem-Restore), **Manager**
  (Pflege von Applikationen und Struktur), angemeldete **Benutzer ohne
  Pflegerechte** (Hilfe und eigenes Konto). Durchgesetzt serverseitig über
  Middleware (`EnsureAdmin`, `EnsureManager`) an den Routen.
- **Gesperrte Konten** können sich nicht anmelden und verlieren eine
  laufende Sitzung beim nächsten Seitenaufruf (`EnsureActiveAccount`).
- Anmeldeversuche sind gedrosselt (10 je Minute und Adresse, dazu
  10 Fehlversuche je Benutzername → 30 Minuten Sperre); Sitzungen werden
  beim Login neu erzeugt (Schutz gegen Session-Fixation), CSRF-Schutz auf
  allen Formularen, Abmelden beendet die Sitzung serverseitig. Es gibt
  kein „Angemeldet bleiben“ (kein Erinnerungs-Cookie).
- **Demo-Konten** (`admin/admin`, `manager/manager`) gelten nur im
  Demo-Modus. Läuft dieselbe Datenbank danach im Normalbetrieb, bekommen sie
  beim Start ein neues Startpasswort; `manager` wird gesperrt.

## Ausgabe und Eingaben
- Blade maskiert alle Ausgaben; Texte für `confirm()`-Rückfragen gehen
  über `@js()`, damit Namen mit Apostroph kein Skript werden.
- Links der Applikationen müssen gültige Adressen sein; `javascript:`,
  `vbscript:`, `data:` und `blob:` lehnt LinkHub ab.
- Impressum und Datenschutz als Klartext: LinkHub erzeugt daraus nur
  Absätze, Überschriften und Links (http/https, E-Mail), alles andere wird
  maskiert.
- Der Sprachumschalter springt nur auf eigene Seiten zurück, nie auf eine
  fremde Adresse aus dem Referer.

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
- Jede Sicherung enthält den App-Schlüssel (`database/.app_key`), damit
  Servicekonto-Passwort und Zwei-Faktor-Geheimnisse nach einer
  Wiederherstellung auf neuer Hardware lesbar sind. Wer eine Sicherung
  hat, kann sie entschlüsseln: Die ZIP-Datei ist nur für Besitzer und Gruppe
  lesbar (`0640`), der Ordner `backups` gehört nur Administratoren.
  Sicherungen bleiben auf dem Server, es gibt keinen Download im Browser.
- Testsystem-Restore verifiziert die Prüfsumme vor dem Einspielen und liest
  aus dem ZIP nur die Datenbank; den Schlüssel der Produktion übernimmt das
  Testsystem nicht. Danach ist die Sitzung beendet (die Konten sind die der
  Produktion).

## Plattform
- SQLite im WAL-Modus in einem eigenen Docker-Volume; Uploads getrennt im
  Storage-Volume; der Backup-Ordner ist der einzige Bind-Mount.
- Logo-Uploads werden validiert (Bildtypen PNG/JPG/SVG/WebP, max. 2 MB)
  und mit generiertem Dateinamen unter `public/uploads` abgelegt (nie mit
  Original-Dateinamen); die Endung folgt dem geprüften Inhalt, nicht dem
  Namen der hochgeladenen Datei.
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

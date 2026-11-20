# Changelog — LinkHub (Link- und Applikationsportal)

Format angelehnt an „Keep a Changelog“. Versionsnummern im Schema
**JJMMTTNN**: Datum plus laufende Nummer des Tages (26101701 = erste
Version am 17.10.2026). Neue Einträge kommen unter „Unveröffentlicht“;
`tools/release.sh` macht daraus die nächste Version. Ältere Stände
tragen Nummern nach MAJOR.MINOR.PATCH.

## [Unveröffentlicht]

## [26112001] — 2026-11-20

### Neu

- **Startpasswort muss geändert werden:** Wer sich mit einem Passwort anmeldet, das LinkHub oder ein Administrator vergeben hat, landet auf **Mein Konto** und erreicht bis zur Änderung nur diese Seite und das Abmelden, auch keine Hilfe. Das gilt für den ersten Administrator mit Zufallspasswort aus dem Protokoll, für neu angelegte Konten, nach „Passwort zurücksetzen“, für ein Passwort aus dem Bearbeiten-Formular, für die neuen Startpasswörter der Demo-Konten nach dem Wechsel in den Normalbetrieb und nach dem Notzugang. Nicht für Verzeichniskonten, nicht im Demo-Modus und nicht für `ADMIN_PASSWORD`. Die Benutzerliste zeigt solche Konten mit dem Abzeichen „Startpasswort“. Eine neue Migration legt die Spalte `users.must_change_password` an; bestehende Konten sind nicht betroffen.
- **Notzugang ohne zweiten Faktor:** `php artisan linkhub:admin-password [name] --disable-2fa` setzt zusätzlich die Zwei-Faktor-Anmeldung des Kontos zurück, z. B. wenn das Handy weg ist. Der Befehl legt wie bisher bei Bedarf ein eigenes Admin-Konto an; das ausgegebene Passwort ist jetzt ein Startpasswort, laufende Sitzungen des Kontos enden.

### Entfernt

- Der Fuchs-Helfer Rua ist weg, ohne Ersatz: keine Figur mehr unten rechts, kein Abschnitt „Fuchs-Helfer“ in den Einstellungen, kein Schalter auf der Seite **Mein Konto**. Die Einstellungen sind neu nummeriert: **6 · Impressum und Datenschutz** (vorher 7).
- Eine neue Migration entfernt beim Start die Spalte `users.fox` und die Einstellung `fox_enabled` (auch die alten Namen `users.fuchs` und `fuchs_aktiv` aus zurückgespielten Sicherungen). Die Adresse `/account/fox` gibt es nicht mehr. Alte Merkwerte des Fuchses im Browser (`lh-fox-…`, `lh-fuchs-…`) räumt LinkHub beim nächsten Seitenaufruf selbst ab.

### Geändert

- **„Manual“ heißt „Anleitung“:** Auf der Kachel, in der Liste und im Formular steht in der deutschen Oberfläche jetzt „Anleitung“ statt „Manual“ (englisch weiter „Manual“).
- Die Oberfläche siezt jetzt durchgehend; die Du-Texte des Fuchses sind entfallen. Der leere Zustand des Portals sagt, was zu tun ist: Manager bekommen den Weg zu „Neu anlegen“, bei einem leeren Filter gibt es einen Link auf alle Applikationen.
- Fehlermeldungen in Formularen sind vollständig deutsch bzw. englisch und nennen das Feld so wie die Oberfläche („Link zur Applikation“ statt „url“). Vorher kamen einige Meldungen auf Englisch, z. B. bei einem schon vergebenen Namen oder einer falschen Passwort-Wiederholung.
- Am Telefon: Die Tabellen der Hilfe stehen untereinander statt abgeschnitten, die Struktur-Tabellen erscheinen als Karten mit erreichbaren Knöpfen, Suchfelder haben die volle Breite, Filter und Knöpfe sind mindestens 44 px hoch.
- Barrierefreiheit: Alle Eingabefelder haben verknüpfte Beschriftungen, der Tastaturfokus ist überall sichtbar, die gedämpfte Schriftfarbe erreicht WCAG AA, und mit „Bewegung reduzieren“ im System entfallen Animationen.
- Die Konto-Seite zeigt auch die Rolle „Manager“. Im Audit-Log erscheinen Einträge gelöschter Konten als „gelöschtes Konto #id“ statt als „System“.
- Meldungen des Testsystems beim Einspielen des Produktionsstands gibt es jetzt auch auf Englisch.
- **Sicherungen enthalten den App-Schlüssel:** Nächtliche Sicherungen und „Backup jetzt erstellen“ legen `/data/.app_key` als `database/.app_key` neben die Datenbank. Damit sind Servicekonto-Passwort und Zwei-Faktor-Geheimnisse nach einer Wiederherstellung auf neuer Hardware lesbar. Die ZIP-Dateien sind nur noch für Besitzer und Gruppe lesbar (`0640`); zum Prüfen oder Kopieren auf dem Host `sudo` verwenden. Den Ordner `backups` dürfen nur Administratoren lesen. Einen Download im Browser gibt es weiterhin nicht.
- **Testsystem:** Nach dem Einspielen des Produktionsstands meldet LinkHub ab; angemeldet wird mit einem Konto der Produktion. Vorher lief die Sitzung unter der Kennung weiter, die in der neuen Datenbank womöglich jemand anderem gehört. Aus der Sicherung liest das Testsystem nur die Datenbank, nicht den App-Schlüssel der Produktion.
- **Gruppen beim Speichern als DN:** Unter **Einstellungen** wird ein Gruppenname bei „Gruppe: Administratoren“, „Gruppe: Manager“ und „Verzeichnisgruppen mit Pflicht“ zum vollständigen DN. Gibt es den Namen in mehreren OUs, erscheint der Fehler am Feld und nichts wird gespeichert; die Eingaben bleiben im Formular stehen. Ist das Verzeichnis nicht erreichbar oder kennt den Namen nicht, bleibt die Eingabe, wie sie ist.
- Steht nach der Anmeldung noch ein Startpasswort oder die Einrichtung der Zwei-Faktor-Anmeldung aus, geht es direkt auf **Mein Konto** statt aufs Portal.
- Das eigene Passwort lässt sich unter **Benutzer** nicht mehr „zurücksetzen“ (die Sitzung endete, bevor das neue Passwort zu sehen war); dafür gibt es **Mein Konto** oder das Feld im Bearbeiten-Formular.

### Behoben

- Verzeichniskonten mit Umlauten oder Leerzeichen im Benutzernamen ließen sich unter **Benutzer** nicht sperren und keine Zwei-Faktor-Ausnahme bekommen; das Speichern scheiterte an der Prüfung für eigene Konten.
- `/structure/<typ>/<keine Zahl>` endete mit Fehler 500; jetzt kommt die passende Fehlerseite.
- Eine Applikation mit einem unbekannten Bereich oder einer unbekannten Abteilung führte zu Fehler 500; jetzt erscheint eine Meldung.

### Sicherheit

- **Passwort des Servicekontos geht nicht mehr an fremde Server:** Ändert man unter Einstellungen → Anmeldung Server, Port, Verschlüsselung oder Servicekonto, ohne das Passwort neu einzugeben, gilt das alte nicht mehr. Bisher schickte „Verbindung testen“ es an den neuen, womöglich fremden Server (auch das Passwort aus `docker-compose.yml`). Nach einem Serverwechsel das Passwort also neu eintragen.
- **Keine Passwörter mehr im Fehlerprotokoll:** War das Verzeichnis nicht erreichbar, stand der Anfang des eingegebenen Passworts im Stacktrace von `laravel.log`. Passwort-Parameter sind jetzt als sensibel markiert, und `docker/php.ini` setzt `zend.exception_ignore_args = On`, `expose_php = Off` und `display_errors = Off`.
- **Verzeichnis mit Zeitlimit je Abfrage:** Ein Server, der die Verbindung annimmt und nicht antwortet, blockiert die Anmeldung nicht mehr unbegrenzt.
- **Bestätigung im Konto gebremst:** Passwort bzw. App-Code zum Ausschalten der Zwei-Faktor-Anmeldung und für neue Wiederherstellungscodes: nach 5 Fehlversuchen 5 Minuten Pause; ein App-Code gilt nur einmal.
- **Demo-Modus aus:** `docker-compose.yml` wird mit `DEMO_MODE: "false"` ausgeliefert; den Demo-Modus schaltet man zum Ausprobieren selbst ein.
- **Hochgeladene Dateien:** `/uploads/` liefert nur noch Bilder aus, mit eigener Content-Security-Policy (`sandbox`) und `nosniff`; ein SVG-Logo kann so kein Skript mehr ausführen.
- **Demo-Konten im Normalbetrieb:** Wer erst im Demo-Modus startet und dann `DEMO_MODE` auf `"false"` stellt, behielt bisher `admin/admin` und `manager/manager`. Jetzt bekommt jedes Demo-Konto, das noch sein Demo-Passwort hat, beim Start ein neues Startpasswort (einmal im Container-Protokoll; für `admin` gilt `ADMIN_PASSWORD`, wenn gesetzt). Konten ohne Admin-Rechte werden zusätzlich gesperrt.
- **Kein „Angemeldet bleiben“ mehr:** Die Anmeldung setzte ein Erinnerungs-Cookie (400 Tage), wenn das Formular `remember` mitschickte, obwohl es dafür kein Kästchen gibt. Damit ließen sich Verzeichnis, Sperre, Passwortwechsel und Zwei-Faktor-Pflicht umgehen. Eine neue Migration löscht alle gespeicherten Erinnerungs-Tokens; alte Cookies gelten nicht mehr.
- **Zwei-Faktor-Pflicht sofort:** Die Stufe wird bei jeder Anfrage neu bestimmt (die strengere aus Anmeldung und aktueller Richtlinie). Wird die Richtlinie strenger oder jemand Manager, geht es ohne eingerichteten zweiten Faktor gleich nur noch auf **Mein Konto**, nicht erst nach der nächsten Anmeldung.
- Bei Stufe „aus“ lässt sich der zweite Faktor auch über die Bestätigungsadresse nicht mehr einschalten. „Protokoll leeren“ prüft die Admin-Rechte jetzt auch an der Route, nicht nur im Controller.
- Löschen-Rückfragen: Ein Name mit Apostroph (Applikation, Bereich, Abteilung, Kategorie) konnte im `confirm()` als Skript laufen, sobald jemand auf „Löschen“ klickte. Die Texte gehen jetzt maskiert über `@js()`.
- Gesperrte Konten verlieren ihre laufende Sitzung beim nächsten Seitenaufruf, nicht erst bei der nächsten Anmeldung. Wer zwischen Passwort und Zwei-Faktor-Code gesperrt wird, kommt nicht mehr hinein.
- Firmenlogo: Die Dateiendung folgt dem geprüften Inhalt, nicht dem Dateinamen; „logo.html“ mit Bildinhalt wird nicht mehr als Seite ausgeliefert.
- Links der Applikationen: `data:`-, `blob:`-, `javascript:`- und `vbscript:`-Adressen werden abgelehnt.
- Der Sprachumschalter leitet nur noch auf eigene Seiten zurück, nie auf eine fremde Adresse aus dem Referer.
- **Passwortwechsel beendet die anderen Sitzungen:** Nach dem eigenen Wechsel, „Passwort zurücksetzen“ durch einen Administrator, einem Passwort aus dem Bearbeiten-Formular und dem Notzugang enden alle anderen Sitzungen des Kontos beim nächsten Seitenaufruf (Laravels `AuthenticateSession`); die Anmeldeseite sagt, warum. Beim eigenen Wechsel bleibt die laufende Sitzung. Bisher blieb ein geklautes oder vergessenes Gerät angemeldet.
- **Gruppen eindeutig:** Ein Gruppenname in den Einstellungen zählte für jede gleichnamige Gruppe in jeder OU, z. B. eine selbst angelegte „LinkHub-Admins“ in einer anderen OU. Jetzt wird er beim Speichern zum DN, doppelte Namen werden abgelehnt.

### Dokumentation

- `SECURITY.md` beschreibt die bekannte Folge des Standardwerts von `TRUSTED_PROXIES`: Geräte im internen Netz können ihre Adresse vortäuschen und die Anmeldebremse je Adresse umgehen; wer das vermeiden will, trägt nur die IP des Reverse Proxys ein.
- Benutzerhandbuch neu: ohne Du, mit Anmeldung, Zwei-Faktor, **Mein Konto** und Hilfe; der Menüpunkt „Pflege“, den es nicht gibt, ist raus.
- Betrieb (`installation/betrieb.md`, englisch `operations.md`): Wiederherstellung mit App-Schlüssel, Umzug, Notzugang mit `--disable-2fa`. Der Notfall „Kein Administrator kommt hinein“ nannte verschachtelte Gruppen als „nicht ausgewertet“ und eine Einstellung `LDAP_LOGGING`, die es nicht gibt; beides ist korrigiert. `SECURITY.md` beschreibt eigene Konten statt „Anmeldung nur über Active Directory“ und siezt.
- Synology-Anleitung: Erste Anmeldung im Normalbetrieb mit dem Startpasswort aus dem Protokoll statt „mit einem Konto aus der Administratoren-Gruppe“.
- Sicherheitsarchitektur an den Stand angepasst (eigene Konten, Verzeichnis ohne LdapRecord, verschachtelte Gruppen, Zwei-Faktor, Uploads); Überblick, Architektur, Admin-Handbuch und README nachgezogen, Rollen um „Benutzer ohne Pflegerechte“ ergänzt.

## [26101802] — 2026-10-18

### Dokumentation

- Beiträge: Fehler, Ideen und Fragen als Issue melden; Pull Requests sind derzeit nur für eingeladene Mitwirkende möglich (`CONTRIBUTING.md`).

## [26101801] — 2026-10-18

### Neu

- Rua lässt sich verschieben: mit Maus oder Finger an jede Stelle ziehen, der Browser merkt sich die Lage (auch über Seitenwechsel und Größenänderungen). Ein kurzer Tipp befragt ihn weiter; die Sprechblase klappt zur freien Seite.
- Rua lobt passend zum Anlass: nach Löschen, Passwort, Zwei-Faktor, Token, Sicherung, Abbrechen oder Abmelden nur schlicht „Erledigt.“, sonst wie bisher mit Bild. Erkannt wird der Anlass von einer neuen Middleware (`FoxOccasion`) an Methode und Route der abgeschickten Aktion.
- Rua hilft bei Fehlern und beim Warten: Kommt eine Seite mit einer Fehlermeldung zurück, sagt er „Gegenwind. Versuch es nochmal, ich bleib an Deck.“ oder „Da fehlt noch etwas. Schau dir das markierte Feld an.“ (auf Anmelde-, Konto-, Passwort- und Zwei-Faktor-Seiten ohne Bild); dauert das Absenden eines Formulars länger als 0,7 s, sagt er „Die Ruder sind im Wasser …“. Formulare mit `data-no-fox` lässt er in Ruhe.

### Geändert

- Repository nach den Gepflogenheiten offener Projekte: Verhaltenskodex (`CODE_OF_CONDUCT.md`), Vorlagen für Fehlermeldungen, Vorschläge und Pull Requests; Sicherheitslücken lassen sich direkt vertraulich melden. Die Werkzeuge der Betreuer (Release, Tests, Prüfungen) liegen nicht mehr im öffentlichen Repo.
- Architektur und Admin-Handbuch plattformneutral: Docker-Host statt „Synology NAS“, Container-Oberflächen nur noch als Beispiel; Versionsangaben im Architekturbild auf PHP 8.4 / Laravel 13 korrigiert.
- **Englische Namen im Code (Standard 10):** Klassen, Methoden, Variablen, Routen, Ansichten, CSS-/JS-Namen, Datenbankspalten, gespeicherte Werte, Einstellungsschlüssel, Befehle und Dateien heißen jetzt englisch; die Oberfläche bleibt unverändert deutsch und englisch. Die vollständige Liste alt → neu steht in `docs/RENAMING.md`.
  - **Automatische Umstellung:** Die neue Migration `rename_to_english` stellt beim ersten Start die Datenbank um (Spalte `users.fuchs` → `users.fox`, Einstellungsschlüssel wie `fuchs_aktiv` → `fox_enabled`, `ldap_aktiv` → `ldap_enabled`, `mfa_gruppen_pflicht` → `mfa_required_groups`, `mfa_sonst` → `mfa_other`, `impressum_*`/`datenschutz_*` → `imprint_*`/`privacy_*`, Zwei-Faktor-Stufen `pflicht`/`aus` → `required`/`off`). Sie ist idempotent: auch eine alte Sicherung wird beim nächsten Start nachgezogen. `app:upgrade` legt vorher wie immer eine Sicherung an. Das Audit-Log mit seiner Hash-Kette bleibt unverändert.
  - **Neue Adressen, alte leiten um:** z. B. `/hilfe` → `/help`, `/konto` → `/account`, `/applikationen` → `/applications`, `/struktur` → `/structure`, `/einstellungen` → `/settings`, `/benutzer` → `/users`, `/impressum` → `/imprint`, `/datenschutz` → `/privacy`, `/sprache/en` → `/locale/en`, Handbücher `/hilfe/handbuch/benutzer` → `/help/manual/user`. Alle alten Seitenadressen leiten dauerhaft (301) um, die Abfrage bleibt erhalten — Lesezeichen gelten weiter. Die Filter des Portals heißen `?area=…&department=…`; alte Lesezeichen mit `?bereich=…&abteilung=…` zeigen weiter dieselben Kacheln.
  - **Einmal neu anmelden:** Die Daten der Sitzung haben neue Namen; nach dem Update meldet sich jeder einmal neu an.
  - **Befehle:** `linkhub:admin-passwort` heißt jetzt `linkhub:admin-password` (der alte Name gilt weiter).
  - Der Fuchs merkt sich „schlafen“ jetzt unter `lh-fox-sleep` (alter Wert im Browser wird übernommen).
- Anleitungen: Abschnitt zu einem Tunnel-Dienst entfernt; die Hilfe bei „Zu viele Weiterleitungen“ ist jetzt allgemein für vorgeschaltete Proxys formuliert.
- Der Fuchs-Helfer heißt jetzt **Rua, der Fuchs**: ein Wikinger-Fuchs aus einem norwegischen Fjorddorf, den die Iren „den Roten“ nannten. Alle seine Texte sind neu und in Du-Form (die übrige Oberfläche bleibt beim „Sie“), ernste Hinweise zu Passwörtern und Audit-Log ohne Bilder; neue Zeichnung mit Umhang, Fibel, Runen-Amulett und schiefem Ohr.

### Behoben

- Rua hat keinen grauen Kasten mehr hinter sich: allgemeine Knopf-Regeln (z. B. im Dunkelmodus) gaben der Figur eine Fläche; jetzt hat sie ausdrücklich keine.
- **Fuchs reagiert wieder auf Klicks und Fingertipps:** `.fuchs-figur { all: unset }` setzte auch `pointer-events` zurück, die Figur erbte `none` vom Rahmen – echte Klicks gingen durch den Fuchs hindurch (Skript-Klicks per `.click()` zeigten den Fehler nicht). `pointer-events: auto` steht jetzt direkt dahinter.

## [26101702] — 2026-10-17

### Neu
- **Zwei-Faktor-Anmeldung (TOTP)** mit Authenticator-App — je Gruppe als Pflicht, wählbar oder aus (Einstellungen → 2 · Anmeldung): Administratoren, Manager, alle übrigen; zusätzlich Verzeichnisgruppen mit Pflicht und Ausnahmen je Benutzer (Benutzer → Bearbeiten). Einrichtung mit QR-Code und Wiederherstellungscodes unter **Mein Konto**; Administratoren setzen den zweiten Faktor bei verlorenem Handy zurück.
- Neue Seite **Mein Konto** (Name oben rechts): Passwort ändern (eigene Konten), Zwei-Faktor, Fuchs-Helfer.
- **Hilfe** nach dem gemeinsamen Standard (`/hilfe`, alte Adresse `/support` leitet um): Kurzanleitung je Rolle, System mit Anmeldeart und eigenem Konto, Lizenz, Handbücher als formatierte Seiten, Änderungshistorie, letzte Fehler mit „Protokoll leeren“, Support.
- **Anmeldung mit und ohne Active Directory** (Standard 09): LinkHub läuft jetzt vollständig mit eigenen Konten. Neuer Menüpunkt **Benutzer** für Administratoren: eigene Konten anlegen, Rechte (Manager, Administrator) vergeben, Passwort zurücksetzen (Startpasswort wird einmal angezeigt), Konten sperren. Verzeichniskonten erscheinen in derselben Liste mit Herkunft „Verzeichnis“.
- **Einstellungen → 2 · Anmeldung**: Verzeichnis (Active Directory, Samba AD, OpenLDAP, Univention) in der Oberfläche einrichten — Server, Port, LDAPS/STARTTLS, Zertifikatsprüfung, Suchbasis, Servicekonto (Passwort verschlüsselt gespeichert), Benutzerattribut und -filter — mit Knopf **„Verbindung testen“**. Gruppen dürfen als DN oder nur als Gruppenname eingetragen werden; verschachtelte AD-Gruppen werden aufgelöst.
- Erster Administrator als eigenes Konto beim ersten Start (`ADMIN_USERNAME`, `ADMIN_PASSWORD`; leer = zufälliges Startpasswort im Container-Protokoll). Notzugang: `php artisan linkhub:admin-passwort`.
- **Fuchs-Helfer** wie in BilDaBooky: begrüßt angemeldete Benutzer, gibt Tipps passend zu Rolle und Seite, wirbt auf der Anmeldeseite für freie Software; auf dem öffentlichen Portal erscheint er nicht. Zentral abschaltbar (Einstellungen → 6), je Person unter **Hilfe**.
- Einheitliche Symbole: Emojis der Oberfläche werden als Twemoji-Grafiken gezeichnet und sehen auf jedem Gerät gleich aus (`werkzeuge/symbole.py` sammelt die benötigten Grafiken ein).
- Handbücher unter **Hilfe** werden formatiert angezeigt (Markdown statt Rohtext); Hilfe-Seite mit Kurzanleitung, PHP/Laravel-Version, Anmeldeart und Links auf Quellcode und Projektseite.
- Downgrade-Schutz beim Start (`php artisan app:upgrade`): Versionsabgleich, Sicherung der Datenbank nach `/data/backups/` vor jeder Änderung, Migrationen. Ist die Datenbank neuer als das Abbild, startet der Container nicht.
- Impressum und Datenschutz: unter **Einstellungen → 5** einzeln einschaltbar, als eigener Text oder Link; ohne Anmeldung erreichbar (`/impressum`, `/datenschutz`), verlinkt auf der Anmeldeseite und im Footer. Mit Vorlage für die Datenschutzhinweise.
- Eigene Fehlerseiten (403, 404, 419, 429, 500, 503) im Erscheinungsbild der Anwendung.
- Footer nach dem gemeinsamen Standard: Produkt, Edition, Version, „freie Software (AGPL-3.0)“, Hilfe, Impressum, Datenschutz und die Zeile „Steffen Mai · www.mai-digital.de“.

### Geändert
- LdapRecord wird nicht mehr benötigt; die Anbindung läuft über die PHP-Erweiterung `ldap` (`App\Support\Verzeichnis`). Bestehende Installationen mit `LDAP_*`-Variablen in der Compose-Datei laufen unverändert weiter; vorhandene AD-Konten werden beim Update als Verzeichniskonten gekennzeichnet.
- `.env.example` und `docker-compose.yml`: LDAP-Werte sind nur noch auskommentierte Beispiele; neu `ADMIN_USERNAME`/`ADMIN_PASSWORD`.
- Nummerierung der Einstellungen: 2 Anmeldung, 3 Rollen aus Gruppen, 4 Lizenz, 5 Backup, 6 Fuchs-Helfer, 7 Impressum und Datenschutz.
- Kopfzeile: Marke verlinkt auf das Portal; Knöpfe und Menüpunkte mindestens 44 px hoch (Standard 03).
- `APP_KEY` wird beim ersten Start erzeugt und in `/data/.app_key` abgelegt. Ein in der Compose-Datei gesetzter Schlüssel gilt weiter. Ohne gesetzten Schlüssel müssen sich alle Benutzer nach diesem Update einmal neu anmelden.
- `TRUSTED_PROXIES` steht standardmäßig auf den internen Netzen statt `*`. Läuft der Reverse Proxy außerhalb dieser Netze, seine Adresse eintragen.
- PHP 8.4 und Laravel 13 (LdapRecord 3.4, Activitylog 4.12).
- „Support & Lizenz“ heißt jetzt **Hilfe**; Handbuch und Changelog sind dort verlinkt.
- Der Scheduler läuft als `www-data`.

### Behoben
- Docker-Bau: Leserechte der Dateien werden im Abbild gesetzt — auch wenn der Bauhost die Dateien mit Modus 600 ablegt (sonst „Forbidden“ wegen unlesbarer `.htaccess`).

### Sicherheit
- Sicherheits-Header für jede Antwort: Content-Security-Policy, `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, hinter HTTPS HSTS und sichere Cookies.
- Zusätzlich zur Drosselung je Adresse: nach 10 Fehlversuchen ist ein Benutzername 30 Minuten gesperrt.

### Dokumentation
- Anleitungen neu geschrieben und nach `installation/` verschoben: `docker.md`, `synology.md`, `reverse-proxy.md`, `betrieb.md`, englisch unter `installation/en/`.

## [26101701] — 2026-10-17

### Geändert
- LinkHub ist Open Source: Lizenz AGPL-3.0-or-later (`LICENSE`, vorher „proprietary“), dazu `CONTRIBUTING.md`, `SECURITY.md` und eine englische README (`README.en.md`).
- Neues Versionsschema JJMMTTNN statt MAJOR.MINOR.PATCH. Bestehende Installationen ab 2.1.x aktualisieren wie gewohnt; die Migrationen laufen beim Start.
- Docker-Abbild heißt jetzt `linkhub:latest` bzw. `linkhub:<version>`; das Testsystem baut ein eigenes Abbild `linkhub:test` und kann die Produktion so nicht mehr überschreiben.
- Abbild trägt Version und Lizenz als Kennzeichnung; GitHub baut es bei jedem Versions-Tag für amd64 und arm64 (`.github/workflows/docker.yml`).

### Dokumentation
- README neu gegliedert (Funktionen, Rollen, Schnellstart, Einstellungen, Testsystem, Versionen, Entwicklung).
- Neue Anleitungen `docs/ANLEITUNG-DOCKER.md`, `docs/ANLEITUNG-SYNOLOGY.md` und `docs/ANLEITUNG-BETRIEB.md`, englische Fassungen unter `docs/en/`.
- Installation per `git clone` / ZIP von GitHub.

### Werkzeuge
- `werkzeuge/version.py` und `werkzeuge/release.sh` für Versionen und Veröffentlichung, `werkzeuge/sprachen.py` prüft die Übersetzungen.

## [2.1.3] — 2026-07-18
### Behoben
- **Logo-Upload schlug mit Fehler 500 fehl**: Der Upload-Ordner
  `public/uploads` liegt in einem eigenen Docker-Volume; ein frisch
  angelegtes Volume gehört root, sodass der Webserver dort nicht
  schreiben konnte. Der Container-Start setzt die Rechte jetzt bei
  jedem Start (wie in CrewGate) — für App- und Scheduler-Container.
- Der Upload bricht auch bei ungünstigen Rechten nicht mehr mit einer
  Fehlerseite ab, sondern meldet den Grund verständlich im Formular;
  das bisherige Logo bleibt in diesem Fall erhalten.
- **SVG-Logos werden wieder angenommen** (Laravel schließt SVG bei der
  `image`-Regel inzwischen aus — die Prüfung läuft jetzt über die
  erlaubten Dateitypen PNG, JPG, SVG und WebP).

## [2.1.2] — 2026-07-18
### Behoben
- **Veraltete Templates nach Updates**: Die kompilierten Blade-Templates
  liegen im persistenten Storage-Volume und überlebten das Neuerstellen
  des Projekts — Laravel lieferte dadurch nach einem Update weiterhin den
  alten Template-Code aus (u. a. der 500er auf der Applikations-Liste
  blieb bestehen, obwohl der Quellcode längst gefixt war). Der
  Container-Start verwirft jetzt bei jedem Start alle kompilierten
  Templates (`view:clear`), Updates greifen damit sofort.

## [2.1.1] — 2026-07-18
### Behoben
- **Update ohne FRESH_DB repariert**: Bestandsdatenbanken aus 2.0.0/2.1.0
  werden jetzt beim Start automatisch hochmigriert — die englischen
  Anzeigefelder werden nachgezogen und die alte kategorisierte
  Settings-Tabelle wird in das neue flache Schema überführt (gepflegte
  Werte wie Firmenname, Farben, AD-Gruppen, Lizenz und Aufbewahrung
  bleiben erhalten). Vorher konnten auf nicht zurückgesetzten
  Datenbanken Fehler 500 auftreten (fehlende Spalten) und englische
  Inhalte fehlten. Einzig das Firmenlogo muss nach dem Update einmalig
  neu hochgeladen werden (neuer Ablageort public/uploads).
- Applikations-Liste: Sichtbarkeits-Anzeige robuster umgesetzt
  (eigene Modell-Methode statt Template-Magie)
- Demo-Daten enthalten jetzt englische Namen und Beschreibungen für
  alle Applikationen; auf bestehenden Demo-Datenbanken werden leere
  EN-Felder beim Start einmalig ergänzt
- Fehlersuche: `APP_DEBUG`-Schalter in beiden Compose-Dateien
  dokumentiert (zeigt bei Bedarf die genaue Fehlermeldung)

## [2.1.0] — 2026-07-17
### Hinzugefügt
- **Zweisprachige Oberfläche (Deutsch/Englisch)** nach dem
  CrewGate-Muster: Sprachumschalter mit Flaggen im Header (Wahl wird in
  der Session gemerkt, Erstwahl nach Browsersprache), vollständige
  englische Übersetzung aller Oberflächentexte (`lang/en.json`)
- **Zweisprachige Inhalte**: optionale englische Namen für Bereiche,
  Abteilungen und Kategorien sowie englischer Name/Beschreibung je
  Applikation — die Anzeige folgt der gewählten Sprache
- **Erweiterte Individualisierung** wie bei CrewGate: Firmenname ersetzt
  „LinkHub" im Header, Header-, Akzent- und Footer-Farbe getrennt per
  Farbwähler, frei definierbarer Footer-Text
- Support-Seite mit System-Panel (Produkt, Version, Build, Systemmodus)

### Geändert
- **UI vollständig an die aktuelle CrewGate-Version angeglichen**:
  identisches Stylesheet-Fundament (Panels, Flash-Meldungen, Pill-Buttons,
  Sprachmenü, rotes TESTSYSTEM-Band, mobile Ansicht), Einstellungen als
  eine Seite mit nummerierten Panels und einem Speichern-Button,
  Struktur-Pflege als Inline-Tabellen (Bearbeiten direkt in der Zeile),
  Login-Seite im Layout mit Versions-/Build-Anzeige
- Sichtbarkeits-Zuordnung im Applikations-Formular jetzt als
  Checkbox-Listen statt Mehrfachauswahl-Feldern
- Einstellungen intern auf flache Schlüssel umgestellt (CrewGate-Schema,
  z. B. `brand_company_name`); Logo liegt jetzt unter `public/uploads`
  (eigenes Docker-Volume `app-uploads`)
- Versionsführung wie CrewGate: `config/version.php` mit Version und
  Build-Zeitstempel; Testsystem-Kennung über `TEST_MODE`
  (vorher `TEST_SYSTEM`)

## [2.0.0] — 2026-07-17
### Hinzugefügt
- **Neuaufbau nach den Werkzeug-Konventionen** (CrewGate / FairShare):
  Glas-Optik mit zentralem Stylesheet, farbiger klebriger Kopfbereich mit
  Pill-Navigation, Hell-/Dunkel-Umschalter oben rechts (merkt sich die
  Wahl), Karten-Layout im gesamten Werkzeug
- **AD-Login über LdapRecord** mit Service-Konto; Rollen `is_admin` und
  `is_manager` werden bei jedem Login aus den AD-Gruppen synchronisiert
  (Listener `SyncAdRoles`, Gruppennamen in den Einstellungen pflegbar)
- **Einstellungen kategorisiert** (Erscheinungsbild mit Logo-Upload und
  Farbwähler, Active Directory, Lizenz, Backup)
- **Lizenzschlüssel** nach dem 20-Zeichen-Schema der Werkzeug-Familie mit
  Auswertung auf der neuen Seite „Support & Lizenz"
- **Audit-Log** (spatie/activitylog) für alle Pflege-Aktionen — mit
  SHA-256-**Hash-Kette** und Prüf-Button (Manipulationsnachweis)
- **Backups**: nächtlich um 01:30 Uhr über den Scheduler-Container,
  Aufbewahrung konfigurierbar (min. 4 Tage); „Jetzt zusätzliches Backup
  erstellen" schreibt nach `backups/manual` mit Prüfsummendatei
- **Testsystem**: `docker-compose.test.yml` (Port 7281, eigene Volumes)
  mit orangem TESTSYSTEM-Band und Ein-Klick-Übernahme des
  Produktionsstands (Einstellungen → Testsystem)
- **In-App-Dokumentation**: Handbücher und Changelog sind im Footer und
  auf der Support-Seite abrufbar (Markdown-Anzeige)
- **Struktur-Pflege mit Reitern** (Bereiche · Abteilungen · Kategorien)
  und Inline-Bearbeitung; zuletzt geöffneter Reiter wird gemerkt
- Portal-**Suche** über Name, Beschreibung und Kategorie

### Geändert
- Docker-Aufbau nach dem CrewGate-Muster: zwei Container (`app` +
  `scheduler`), Named Volumes für DB und Storage, `./backups` als einziger
  Bind-Mount, Compose ohne `.env`-Datei; Ports 7280 (Produktion) und
  7281 (Test)
- Rollenmodell vereinfacht: die AD-Gruppen setzen direkt `is_admin` /
  `is_manager`; die lokale Benutzerverwaltung entfällt (Demo-Benutzer
  kommen aus dem Seeder)
- Versionsführung wie in den übrigen Werkzeugen: Version in
  `config/linkhub.php`, Historie ausschließlich in dieser Datei

### Entfernt
- Eigene Login-/LDAP-Implementierung, Benutzerverwaltung, VERSION-Datei,
  Build-Historie und separater `synology/`-Ordner (die Compose-Dateien im
  Projektstamm sind jetzt das Synology-Projekt)

### Hinweis zum Update von 1.0.0
Datenbankschema und Rollenmodell wurden neu aufgebaut. Bestehende
1.0.0-Installationen einmalig mit `FRESH_DB: "1"` starten (löscht die
Datenbank!) und die Inhalte neu pflegen — oder direkt neu installieren.

## [1.0.0] — 2026-07-17
### Hinzugefügt
- Erstausgabe: öffentliches Portal mit Bereichs-/Abteilungs-Filter,
  Kategorien, Applikations-Kacheln mit Monogramm, Beschreibung und
  Manual-Link; Pflegebereich mit Login; Demo-Modus; Docker/SQLite-Betrieb

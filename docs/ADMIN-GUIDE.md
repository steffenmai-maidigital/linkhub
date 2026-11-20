# Administrations-Handbuch — LinkHub

> Für Administratoren. Wird bei jedem Release mitgepflegt.

## Installation (Docker)
Ausführlich: `installation/docker.md`; Beispiel für eine Container-Oberfläche auf einem NAS: `installation/synology.md`.

1. Quellcode auf den Docker-Host holen (`git clone` oder ZIP von
   GitHub) und im Projektordner den Unterordner `backups/` anlegen
   (manche Container-Oberflächen legen Bind-Mount-Ordner nicht selbst an).
2. In `docker-compose.yml` `DEMO_MODE` auf `"false"` setzen; Port ist standardmäßig 7280.
   Den `APP_KEY` erzeugt LinkHub beim ersten Start selbst (`/data/.app_key`).
   Der erste Administrator `admin` entsteht als eigenes Konto; sein
   Startpasswort steht im Container-Protokoll (oder vorher `ADMIN_PASSWORD`
   setzen). Ein Startpasswort aus dem Protokoll muss bei der ersten
   Anmeldung unter **Mein Konto** geändert werden. Ein Verzeichnis ist optional und wird danach in der Oberfläche
   eingerichtet (**Einstellungen → 2 · Anmeldung**).
3. Starten: `docker compose up -d --build` – oder in einer Container-Oberfläche
   als Projekt erstellen. Beim ersten Start werden
   Datenbank, Migrationen und (im Demo-Modus) Beispieldaten automatisch
   eingerichtet.

Es laufen zwei Container: `app` (Weboberfläche) und `scheduler`
(nächtliches Backup).

## Update
Neuen Stand holen (`git pull` oder neue Dateien kopieren) → Projekt
bereinigen und neu erstellen. Migrationen
laufen bei jedem Start automatisch (`migrate --force`); kompilierte
Templates werden beim Start verworfen, damit Updates sofort greifen.
Ob der neue Stand läuft, zeigt die Login-Seite (Version + Build) — Datenbanken
älterer Versionen werden dadurch hochmigriert, die Daten in den Volumes
bleiben erhalten. `CHANGELOG.md` listet alle Änderungen. Notfall-Reset:
`FRESH_DB: "1"` setzen (löscht die Datenbank!), danach wieder entfernen.
Der Wechsel von 2.0.0 auf 2.1.x wird automatisch migriert (Settings und
EN-Felder); nur das Firmenlogo muss danach einmalig neu hochgeladen
werden. Ausnahme: der Wechsel von 1.0.0 erfordert den Reset (neues
Schema, siehe Changelog).

## Benutzer, Rollen & Verzeichnis
- **Eigene Konten** (Menü **Benutzer**): anlegen, Rechte per Haken
  (Manager, Administrator), Passwort zurücksetzen (Startpasswort wird einmal
  angezeigt), sperren, löschen. Das eigene Konto und der letzte Administrator
  sind geschützt. Ein gesperrtes Konto verliert eine laufende Sitzung beim
  nächsten Seitenaufruf.
- **Startpasswort:** Jedes Passwort, das ein Administrator vergibt (beim
  Anlegen, über „Passwort zurücksetzen“ oder im Bearbeiten-Formular), muss die
  Person bei der nächsten Anmeldung unter **Mein Konto** ändern; bis dahin
  erreicht sie nur diese Seite und das Abmelden. Die Liste zeigt solche Konten
  mit dem Abzeichen „Startpasswort“. Das eigene Passwort ändern Administratoren
  unter **Mein Konto** (oder im Bearbeiten-Formular des eigenen Kontos, dann
  ohne Pflicht zum Ändern). Nicht für Verzeichniskonten, nicht im Demo-Modus.
- **Sitzungen:** Ein neues Passwort beendet alle anderen Sitzungen des Kontos
  beim nächsten Seitenaufruf; die Anmeldeseite sagt dann, warum. Beim eigenen
  Wechsel bleibt die laufende Sitzung.
- **Verzeichnis** (**Einstellungen → 2 · Anmeldung**): Active Directory,
  Samba AD, OpenLDAP oder Univention — Server, Port, LDAPS/STARTTLS,
  Suchbasis, Servicekonto (Passwort verschlüsselt gespeichert), Attribut
  (`sAMAccountName` bzw. `uid`), Filter; Knopf **„Verbindung testen“**.
  Baustein `App\Support\Directory` (PHP-Erweiterung `ldap`).
- **Zwei-Faktor** (**Einstellungen → 2**, unterer Teil): je Gruppe Pflicht,
  wählbar oder aus (Administratoren, Manager, alle übrigen), Verzeichnisgruppen
  mit Pflicht; Einrichtung unter **Mein Konto** mit QR-Code und
  Wiederherstellungscodes; Ausnahme je Benutzer und Zurücksetzen unter
  **Benutzer → Bearbeiten**.
- **Rollen aus Gruppen** (**Einstellungen → 3**): Admin-Gruppe
  (`ad_group_admins`) und Manager-Gruppe (`ad_group_managers`), als DN oder
  Name. Ein Name wird beim Speichern zum vollständigen DN; gibt es ihn in
  mehreren OUs, erscheint am Feld ein Fehler, und nichts wird gespeichert —
  dann den DN eintragen. Dasselbe gilt für die Verzeichnisgruppen mit
  Zwei-Faktor-Pflicht. Ist das Verzeichnis nicht erreichbar oder kennt den
  Namen nicht, bleibt die Eingabe, wie sie ist. Bei **jedem Login** werden
  die Gruppen gelesen (bei AD auch verschachtelt) und `is_admin` /
  `is_manager` gesetzt bzw. entzogen.
- **Administrator**: alles, inkl. Benutzer, Einstellungen, Audit-Log, Backup.
  **Manager**: Pflege von Applikationen und Struktur.
- Verzeichniskonten entstehen automatisch beim ersten Login und erscheinen
  unter **Benutzer** mit Herkunft „Verzeichnis“ (nur ansehen und sperren).
  Demo-Modus: `admin/admin` und `manager/manager` als eigene Konten.
- **Notzugang:** `docker exec linkhub php artisan linkhub:admin-password [name]`
  legt ein eigenes Admin-Konto an (Standard `admin`) oder setzt sein Passwort
  neu. Das ausgegebene Passwort ist ein Startpasswort, laufende Sitzungen des
  Kontos enden. `--disable-2fa` setzt zusätzlich die Zwei-Faktor-Anmeldung
  zurück (Handy verloren). Verzeichniskonten lehnt der Befehl ab.

## Pflege-Logik (Sichtbarkeit)
- Zuordnung an einen **Bereich**: sichtbar im Bereichs-Filter (und unter
  „Alle").
- Zuordnung an eine **Abteilung**: sichtbar im Abteilungs-Filter und im
  Bereichs-Filter des zugehörigen Bereichs (und unter „Alle").
- **Keine Zuordnung**: nur unter „Alle" — für unternehmensweite
  Anwendungen.

## Einstellungen (eine Seite, nummerierte Panels)
- **1 · Firma & Erscheinungsbild**: Firmenname (ersetzt „LinkHub" im
  Header), Logo-Upload mit Vorschau und Entfernen-Option
  (PNG/JPG/SVG/WebP bis 2 MB, liegt unter `public/uploads` im Volume
  `app-uploads`; die Schreibrechte setzt der Container bei jedem Start
  selbst), Header-, Akzent- und Footer-Farbe per Farbwähler,
  freier Footer-Text.
- **2 · Anmeldung**: Verzeichnis einschalten und einrichten (siehe oben).
- **3 · Rollen aus Gruppen des Verzeichnisses**: die beiden Gruppen.
- **4 · Lizenz**: Lizenznehmer und 20-Zeichen-Schlüssel; nur
  schema-konforme Schlüssel werden gespeichert, Auswertung auf der Seite
  „Hilfe“. Ein abgelaufener Support hat keine funktionalen
  Auswirkungen.
- **5 · Backup**: Aufbewahrungstage der automatischen Sicherungen
  (harte Untergrenze 4 Tage, nur nach oben anpassbar).
- **6 · Impressum und Datenschutz**: beide Seiten einzeln einschaltbar, als
  eigener Text oder Link auf die Firmen-Homepage; ohne Anmeldung erreichbar
  (`/imprint`, `/privacy`) und auf Anmeldeseite und Footer verlinkt.
  Für die Datenschutzhinweise gibt es eine Vorlage.
Darunter: „Backup jetzt erstellen" und — nur im Testsystem — die
Übernahme des Produktionsstands.

## Sprache
Die Oberfläche ist zweisprachig (Deutsch/Englisch, Umschalter im
Header). Übersetzungen der Oberfläche liegen in `lang/en.json`
(deutscher Text = Schlüssel). Inhalte (Bereiche, Abteilungen,
Kategorien, Applikationen) haben optionale EN-Felder in der Pflege;
leere EN-Felder fallen auf den deutschen Text zurück.

## Betrieb & Sicherheit
- **Backup**: nächtlich 01:30 Uhr in `backups/` mit Prüfsummen; „Jetzt
  zusätzliches Backup" → `backups/manual` (wird nie automatisch gelöscht).
  Jede Sicherung enthält Datenbank, Logo und den App-Schlüssel
  (`database/.app_key`); ohne ihn wären Servicekonto-Passwort und
  Zwei-Faktor nach einer Wiederherstellung auf neuer Hardware nicht lesbar.
  Den Ordner `backups` und seine Kopien dürfen deshalb nur Administratoren
  lesen; einen Download im Browser gibt es nicht.
  Wiederherstellung: Container stoppen, `database/linkhub.sqlite` und
  `database/.app_key` aus dem Backup-ZIP in das Datenvolume
  (`/data/linkhub.sqlite`, `/data/.app_key`) kopieren, ggf. `uploads/` nach
  `public/uploads` (Volume `app-uploads`) zurückspielen, Container starten.
  Befehle: `installation/betrieb.md`.
- **Testsystem**: `docker-compose.test.yml` als eigenes Projekt starten
  (Port 7281, Ordner `backups-test/` vorher anlegen). Unter
  Einstellungen → Testsystem lässt sich der letzte Produktionsstand
  einspielen; die Prüfsumme wird vorher verifiziert. Danach gelten die Konten
  der Produktion (neu anmelden). Den App-Schlüssel der Produktion übernimmt
  das Testsystem nicht.
- **Audit-Log**: alle Pflege-Aktionen mit Benutzer, Zeit und Feldwerten;
  „Hash-Kette prüfen" weist nachträgliche Manipulation nach.
- Anmeldeversuche sind auf 10 pro Minute gedrosselt (Throttle).

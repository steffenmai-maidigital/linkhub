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
   setzen). Ein Verzeichnis ist optional und wird danach in der Oberfläche
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
  sind geschützt.
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
  Name. Bei **jedem Login** werden die Gruppen gelesen (bei AD auch
  verschachtelt) und `is_admin` / `is_manager` gesetzt bzw. entzogen.
- **Administrator**: alles, inkl. Benutzer, Einstellungen, Audit-Log, Backup.
  **Manager**: Pflege von Applikationen und Struktur.
- Verzeichniskonten entstehen automatisch beim ersten Login und erscheinen
  unter **Benutzer** mit Herkunft „Verzeichnis“ (nur ansehen und sperren).
  Demo-Modus: `admin/admin` und `manager/manager` als eigene Konten.
- Notzugang: `docker exec linkhub php artisan linkhub:admin-password`.

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
- **6 · Fuchs-Helfer** (Rua, der Wikinger-Fuchs aus Norwegen): zentral ein-
  oder ausschalten; jede Person kann ihn unter **Hilfe** für sich ausblenden.
- **7 · Impressum und Datenschutz**: beide Seiten einzeln einschaltbar, als
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
  Wiederherstellung: Container stoppen, `database/linkhub.sqlite` aus dem
  Backup-ZIP in das Datenvolume (`/data/linkhub.sqlite`) kopieren, ggf.
  `uploads/` nach `public/uploads` (Volume `app-uploads`) zurückspielen, Container starten.
- **Testsystem**: `docker-compose.test.yml` als eigenes Projekt starten
  (Port 7281, Ordner `backups-test/` vorher anlegen). Unter
  Einstellungen → Testsystem lässt sich der letzte Produktionsstand
  einspielen; die Prüfsumme wird vorher verifiziert.
- **Audit-Log**: alle Pflege-Aktionen mit Benutzer, Zeit und Feldwerten;
  „Hash-Kette prüfen" weist nachträgliche Manipulation nach.
- Anmeldeversuche sind auf 10 pro Minute gedrosselt (Throttle).

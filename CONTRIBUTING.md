# Mitmachen / Contributing

Schön, dass du helfen möchtest! *(English below.)*

## Grundsätze

1. **Das Portal bleibt öffentlich und harmlos.** Ohne Anmeldung sind nur
   gepflegte Links, Beschreibungen und Kategorien sichtbar — keine
   personenbezogenen oder vertraulichen Daten.
2. **Rechte serverseitig.** Jede Pflegeaktion ist über Middleware
   (`EnsureManager`, `EnsureAdmin`) geschützt; ausgeblendete Knöpfe sind kein
   Schutz.
3. **Alles ins Audit-Log.** Was Daten ändert, wird mit Benutzer, Zeit und
   Feldwerten protokolliert und hängt an der Hash-Kette.
4. **Daten bleiben im Haus.** Keine externen Dienste, CDNs, Schriften,
   Analyse- oder Fehlerdienste.
5. **Kein Build-Schritt.** Kein Node und kein npm: CSS liegt direkt in
   `public/css/app.css`.
6. **Jeder Text übersetzbar.** In PHP und Blade `__('Deutscher Text')`.
   Danach `lang/en.json`
   ergänzen. Neue Inhaltsfelder bekommen eine optionale englische Fassung.
7. **Updates ohne Datenverlust.** Schemaänderungen nur als neue Migration.

## Ablauf

1. Issue anlegen oder kommentieren, was du vorhast.
2. Fork, Branch, Änderung.
3. Lokal testen:
   ```bash
   docker build -t linkhub:dev .
   docker run --rm -p 7280:80 -e DEMO_MODE=true -e APP_DEBUG=true linkhub:dev
   ```
4. Pull Request mit kurzer Beschreibung und, bei Oberflächenänderungen, einem
   Bildschirmfoto (hell und dunkel, Desktop und Telefonbreite).

### Veröffentlichen

Versionen veröffentlichen die Betreuer. Trag deine Änderung bitte im
`CHANGELOG.md` unter „## [Unveröffentlicht]“ ein; Nummer, Git-Tag und
Docker-Abbild kommen beim Veröffentlichen dazu. Vorher prüfen die Betreuer
Übersetzungen, Doku und Sicherheit mit eigenen Werkzeugen.

Es gilt der [Verhaltenskodex](CODE_OF_CONDUCT.md).

Mit einem Beitrag stimmst du zu, dass er unter der AGPL-3.0-or-later
veröffentlicht wird.

## Englische Namen, Kommentare zweisprachig / English names, bilingual comments

**Deutsch.** Alle Bezeichner im Code sind englisch (Standard 10): Klassen,
Methoden, Variablen, Routen und Routennamen, Ansichten, CSS-Klassen,
JS-Namen, Datenbankspalten, gespeicherte Werte, Einstellungsschlüssel,
Artisan-Befehle, Dateien und Ordner. Deutsch bleiben die Oberflächentexte
(deutsche Übersetzungsschlüssel in `__('…')`, Englisch in `lang/en.json`),
die Handbücher unter `docs/` und die deutsche Hälfte der Kommentare.

- **Jeder Kommentar steht auf Deutsch und Englisch**: erst Deutsch, dann
  Englisch; in Docblocks durch eine Leerzeile getrennt, bei Zeilenkommentaren
  als zweite Zeile, am Zeilenende als `// Deutsch / English`.
- Begriffe einheitlich: Applikation = application, Bereich = area,
  Abteilung = department, Kategorie = category, Struktur = structure,
  Verzeichnis = directory, Fuchs-Helfer = fox, Impressum = imprint,
  Datenschutz = privacy, Sicherung = backup, Testsystem = test system.
- Die Einträge im Audit-Log (`activity_log`) bilden eine Hash-Kette und
  behalten ihre gespeicherten (deutschen) Modulnamen und Beschreibungen.
- Wer einen alten Fork oder eine alte Sicherung hat: Die Tabelle aller
  Umbenennungen (alt → neu) steht in [docs/RENAMING.md](docs/RENAMING.md).
  Die Migration `rename_to_english` stellt bestehende Datenbanken beim Start
  automatisch um; alte Adressen leiten dauerhaft um (`routes/legacy.php`),
  alte Filter-Lesezeichen (`?bereich=…&abteilung=…`) gelten weiter.

**English.** All identifiers in the code are English (standard 10): classes,
methods, variables, routes and route names, views, CSS classes, JS names,
database columns, stored values, settings keys, artisan commands, files and
folders. German remains for UI strings (German translation keys in
`__('…')`, English in `lang/en.json`), the manuals under `docs/` and the
German half of the comments.

- **Every comment is written in German and English**: German first, then
  English; in docblocks separated by a blank line, for line comments as a
  second line, at the end of a code line as `// Deutsch / English`.
- Consistent terms: Applikation = application, Bereich = area,
  Abteilung = department, Kategorie = category, Struktur = structure,
  Verzeichnis = directory, Fuchs-Helfer = fox, Impressum = imprint,
  Datenschutz = privacy, Sicherung = backup, Testsystem = test system.
- Audit log entries (`activity_log`) form a hash chain and keep their stored
  (German) module names and descriptions.
- If you have an old fork or an old backup: the table of all renames
  (old → new) is in [docs/RENAMING.md](docs/RENAMING.md). The migration
  `rename_to_english` converts existing databases automatically at start-up;
  old addresses redirect permanently (`routes/legacy.php`), old filter
  bookmarks (`?bereich=…&abteilung=…`) keep working.

## English

- The portal stays public and harmless: without login only maintained links,
  descriptions and categories are visible.
- Enforce permissions on the server (`EnsureManager`, `EnsureAdmin`).
- Everything that changes data goes to the hash-chained audit log.
- No third-party services, CDNs or web fonts.
- No build step (no Node/npm).
- All UI strings must be translatable with `__('German text')`. German
  strings are the keys. Update
  `lang/en.json`.
- Schema changes only as new migrations.

The [Code of Conduct](CODE_OF_CONDUCT.md) applies.

By contributing you agree that your contribution is licensed under the
AGPL-3.0-or-later.

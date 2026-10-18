# App-Dokumentation — LinkHub

> Wird bei jedem Release mitgepflegt. Aktuelle Version: siehe `config/version.php` bzw. Login-/Support-Seite

## Module

| Modul | Zweck | Rolle |
|---|---|---|
| Portal | Applikations-Kacheln mit Filter (Bereiche → Abteilungen), Kategorien-Abschnitten, Suche, Hell-/Dunkel-Modus, Sprache Deutsch/Englisch | öffentlich |
| Applikationen | Anlegen/Bearbeiten mit Links, Beschreibung, Kategorie, Sichtbarkeits-Zuordnung, aktiv/inaktiv | Manager |
| Struktur | Bereiche · Abteilungen · Kategorien als Inline-Tabellen (Bearbeiten direkt in der Zeile, optionale EN-Namen) | Manager |
| Einstellungen | Eine Seite mit nummerierten Panels: Firma & Erscheinungsbild (Name, Logo, drei Farben, Footer-Text), Active Directory & Rollen, Lizenz, Backup | Administrator |
| Audit-Log | Chronologisches Protokoll mit SHA-256-Hash-Kette und Prüf-Button | Administrator |
| Hilfe | Lizenz-Auswertung, Handbücher, Support-Hinweise | angemeldete Benutzer |
| Testsystem | Zweite Instanz (Port 7281) mit Übernahme des Produktionsstands | Administrator |

## Typischer Pflege-Ablauf
1. Struktur prüfen (neuer Bereich? neue Abteilung? neue Kategorie?)
2. Applikation anlegen: Name, Link, Manual-Link, ein Satz Beschreibung
3. Sichtbarkeit zuordnen (Bereich und/oder Abteilungen — oder bewusst
   keine Zuordnung für „Alle")
4. Im Portal kontrollieren (Filter und Suche)

## Update-Verfahren
Neuen Stand holen (`git pull` oder neue Dateien kopieren) → `docker compose up -d --build`
(bzw. in einer Container-Oberfläche das Projekt neu erstellen). Beim Start laufen alle neuen Migrationen automatisch; Daten in
den Volumes bleiben erhalten. Notfall-Reset: `FRESH_DB: "1"` (löscht die DB!).

## Changelog

Die Versionshistorie wird zentral in der Datei `CHANGELOG.md` im
Projektstamm gepflegt und bei jedem Release aktualisiert.

# App-Dokumentation — LinkHub

> Wird bei jedem Release mitgepflegt. Aktuelle Version: siehe `config/version.php` bzw. Anmelde- und Hilfe-Seite

## Module

| Modul | Zweck | Rolle |
|---|---|---|
| Portal | Applikations-Kacheln mit Filter (Bereiche → Abteilungen), Kategorien-Abschnitten, Suche, Hell-/Dunkel-Modus, Sprache Deutsch/Englisch | öffentlich |
| Applikationen | Anlegen/Bearbeiten mit Links, Beschreibung, Kategorie, Sichtbarkeits-Zuordnung, aktiv/inaktiv | Manager |
| Struktur | Bereiche · Abteilungen · Kategorien als Inline-Tabellen (Bearbeiten direkt in der Zeile, optionale EN-Namen) | Manager |
| Benutzer | Eigene Konten anlegen (mit Startpasswort, das die Person bei der ersten Anmeldung ändert), Rechte (Manager, Administrator), Passwort zurücksetzen, Zwei-Faktor zurücksetzen, sperren, löschen; Verzeichniskonten ansehen und sperren | Administrator |
| Einstellungen | Eine Seite mit nummerierten Panels: 1 Firma & Erscheinungsbild (Name, Logo, drei Farben, Footer-Text), 2 Anmeldung (Verzeichnis, Zwei-Faktor), 3 Rollen aus Gruppen, 4 Lizenz, 5 Backup, 6 Impressum und Datenschutz | Administrator |
| Audit-Log | Chronologisches Protokoll mit SHA-256-Hash-Kette und Prüf-Button | Administrator |
| Mein Konto | Passwort ändern (eigene Konten; meldet alle anderen Geräte ab, Pflicht bei einem Startpasswort), Zwei-Faktor-Anmeldung einrichten | angemeldete Benutzer |
| Hilfe | Kurzanleitung, System, Lizenz, Handbücher, Änderungshistorie, letzte Fehler (Administratoren), Support | angemeldete Benutzer |
| Testsystem | Zweite Instanz (Port 7281) mit Übernahme des Produktionsstands (nur die Datenbank, danach neu anmelden) | Administrator |

## Typischer Pflege-Ablauf
1. Struktur prüfen (neuer Bereich? neue Abteilung? neue Kategorie?)
2. Applikation anlegen: Name, Link, Link zur Anleitung, ein Satz Beschreibung
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

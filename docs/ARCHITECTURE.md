# Architektur — LinkHub

> Dieses Dokument wird bei jedem Release mitgepflegt.

## Systemübersicht

```
┌──────────────────── Docker-Host (Server, VM oder NAS) ───────────────┐
│                                                                      │
│  ┌────────────── Docker: app ─────────────┐  ┌── Docker: scheduler ─┐│
│  │ Apache + PHP 8.4 + Laravel 13          │  │ php artisan          ││
│  │ Port 7280 → 80                         │  │ schedule:work        ││
│  │ Entrypoint: Key, Migration, Seed       │  │ (Backup 01:30)       ││
│  └───────┬──────────────┬─────────┬───────┘  └─────────┬────────────┘│
│          │              │         │                    │             │
│   Volume app-data  Volumes app-  Bind ./backups        │             │
│   (SQLite, WAL)    storage +     (Backups + Prüf-      │             │
│          │         app-uploads    summen, manual/)  gleiche Volumes  │
│          └─────────┴──────────────┴─────────────────────┘            │
└──────────────────────────────────────────────────────────────────────┘
                ▲ LDAP/AD: Login + Gruppen→Rollen (User::permissionsFromGroups)
                ▲ Browser der Mitarbeiter: Portal ohne Anmeldung
```

## Fachliche Schichten

```
Portal (öffentlich, HomeController)
  Bereichs-Reiter → Abteilungs-Zeile → „Alle" · Live-Suche (clientseitig)
        │  Filterlogik: Abteilung gewählt → nur direkt zugeordnete Apps;
        │  Bereich gewählt → direkt am Bereich ODER an einer seiner
        │  Abteilungen; ohne Filter → alles Aktive
        ▼
Anzeige-Gruppierung
  nach Kategorie (Sortierung), Einträge ohne Kategorie unter „Weitere";
  Kachel = Monogramm (Farbton deterministisch aus dem Namen) + Name +
  Beschreibung + Öffnen/Manual
        ▼
Pflege (Manager, nach Login)
  Applikationen (Formular mit Sichtbarkeits-Zuordnung, sync der Pivots)
  Struktur mit Reitern: Bereiche · Abteilungen · Kategorien (Inline-Forms,
  Slugs werden automatisch eindeutig vergeben)
        ▼
Verwaltung (Administrator)
  Einstellungen (branding · ad · lizenz · backup, kategorisierte Karten)
  Audit-Log (Hash-Kette) · Backup „Jetzt sichern" · Testsystem-Restore
```

## Datenmodell

```
areas 1─n departments          categories 1─n applications
  │            │                                  │
  └── application_area ──┐          ┌─────────────┘
                         ├── applications (name, url, manual_url,
  departments ───────────┘           description, sort_order, active)
       application_department
```

## Querschnitt

| Thema | Umsetzung |
|---|---|
| Rollen | AD-Gruppen aus den Einstellungen setzen `is_admin` / `is_manager`; Sync bei jedem Login (`User::permissionsFromGroups()`); Portal ohne Login (nur lesend) |
| Audit | spatie/activitylog mit eigener Hash-Kette (`record_hash`, SHA-256 verkettet) und Prüf-Button |
| Backup | nächtlich (VACUUM INTO + Logo-Upload als ZIP), Aufbewahrung ≥ 4 Tage; „Jetzt sichern" → `backups/manual` mit Prüfsummendatei |
| Testsystem | eigene Instanz (Port 7281), Produktions-Backups read-only, Restore mit Prüfsummen-Verifikation und `migrate --force` |
| Versionierung | `config('version.number')` + Build-Zeitstempel, DB-Migrationen laufen bei jedem Containerstart (`migrate --force`) — Updates migrieren automatisch hoch |
| Lizenz | 20-Zeichen-Schlüssel (Typ, Kundennr., Support-Ende, Benutzeranzahl), Zählbasis: registrierte Benutzer |
| Branding | Setting-Tabelle (flache Schlüssel nach CrewGate-Schema); Firmenname ersetzt den Produktnamen, Logo unter `public/uploads`, Header-/Akzent-/Footer-Farbe und Footer-Text als CSS-Variablen bzw. Texte im Layout |
| Sprache | Middleware `SetLocale` (Session + Browsersprache), Route `/locale/{locale}`, UI-Übersetzung `lang/en.json`, EN-Felder an den Inhalten mit `displayName()`-Fallback |

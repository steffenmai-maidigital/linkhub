# Benutzerhandbuch — LinkHub

> Für alle Mitarbeiter sowie Manager. Wird bei jedem Release mitgepflegt.

## Das Portal (ohne Anmeldung)
LinkHub ist die Startseite für alle Web-Applikationen des Unternehmens.
Das Portal öffnet sich im Browser, eine Anmeldung ist **nicht** nötig.

- **Filtern**: Die Leiste oben zeigt die Bereiche (z. B. Produktion);
  nach der Wahl eines Bereichs erscheinen darunter seine Abteilungen.
  „Alle“ zeigt den kompletten Katalog.
- **Suchen**: Das Suchfeld filtert sofort nach Name, Beschreibung und
  Kategorie.
- **Öffnen**: Jede Kachel zeigt Monogramm, Name, Kategorie und eine kurze
  Beschreibung. „Öffnen“ startet die Applikation in einem neuen Tab;
  „Anleitung“ führt zur Anleitung der Applikation, falls hinterlegt.
- **Hell/Dunkel**: Der Umschalter ◐ oben rechts wechselt das Farbschema.
  Der Browser merkt sich die Wahl; ohne Wahl folgt LinkHub der
  Systemeinstellung.
- **Sprache**: Neben dem ◐ sitzt der Sprachumschalter (Flagge). LinkHub
  gibt es auf Deutsch und Englisch. Beim ersten Besuch entscheidet die
  Browsersprache, danach gilt die gewählte Sprache. Sind englische Namen
  und Beschreibungen gepflegt, zeigt die englische Ansicht sie an.

Fehlt eine Applikation oder stimmt ein Link nicht? Dann hilft die IT oder
ein Manager von LinkHub. Ein Eintrag ist in einer Minute geändert.

## Anmeldung
Anmelden müssen sich nur Manager und Administratoren, die das Portal
pflegen (oben rechts **Anmelden**).

- **Eigenes LinkHub-Konto**: Benutzername und Startpasswort vergibt ein
  Administrator. Bei der ersten Anmeldung öffnet sich **Mein Konto**: Erst
  wenn dort ein eigenes Passwort gesetzt ist, geht der Rest von LinkHub auf.
  Dasselbe gilt, wenn ein Administrator das Passwort zurückgesetzt hat.
- **Firmenkonto**: Ist ein Verzeichnis (z. B. Active Directory)
  angebunden, gilt die normale Windows-Anmeldung. Die Rechte ergeben sich
  dann aus den Gruppen im Verzeichnis; Fragen dazu beantwortet die
  Administration.
- **Zwei-Faktor**: Ist sie eingerichtet, fragt LinkHub nach dem Passwort
  einen 6-stelligen Code aus der Authenticator-App ab. Ohne Handy geht
  einer der Wiederherstellungscodes.
- Nach 10 Fehlversuchen ist ein Benutzername 30 Minuten gesperrt.
- **Abmelden**: Knopf oben rechts.

Im Demo-Modus gelten `admin/admin` und `manager/manager`.

## Mein Konto
Ein Klick auf den eigenen Namen oben rechts öffnet **Mein Konto**:

- **Passwort ändern** (nur eigene LinkHub-Konten; Firmenkonten ändern ihr
  Passwort im Verzeichnis). Ein neues Passwort meldet das Konto auf allen
  anderen Geräten ab; die Anmeldeseite sagt dort, warum. Die laufende
  Sitzung bleibt.
- **Zwei-Faktor-Anmeldung** einrichten: Authenticator-App öffnen,
  QR-Code scannen, den angezeigten Code eintragen. Danach erscheinen
  8 Wiederherstellungscodes – nur einmal, also gleich notieren. Neue Codes
  und das Ausschalten verlangen zur Bestätigung das Passwort (Firmenkonten:
  einen aktuellen Code aus der App). Schreibt die Firma den zweiten Faktor
  vor, ist bis zur Einrichtung nur diese Seite erreichbar.

## Pflege (Manager)
Nach der Anmeldung erscheinen oben die Menüpunkte **Applikationen** und
**Struktur**.

### Applikationen
- **Neu anlegen / Bearbeiten**: Name, Beschreibung (ein Satz für die
  Kachel), Link zur Applikation, optional Link zur Anleitung, Kategorie,
  Reihenfolge, aktiv/inaktiv. Name und Beschreibung lassen sich zusätzlich
  auf Englisch pflegen (leer = der deutsche Text erscheint).
- **Links** beginnen mit `https://` (oder `http://`). Adressen, die im
  Browser Code statt einer Seite öffnen (`javascript:`, `data:`), lehnt
  LinkHub ab.
- **Sichtbarkeit**: Die Applikation gehört zu Bereichen (gilt für den
  ganzen Bereich) und/oder zu einzelnen Abteilungen. Ohne Zuordnung
  erscheint sie nur im Filter „Alle“ – sinnvoll für Anwendungen, die alle
  betreffen (z. B. Webmail, Zeiterfassung).
- **Inaktiv** nimmt eine Kachel aus dem Portal, ohne sie zu löschen.
- **Löschen** fragt vorher nach.

### Struktur
Die Seite „Struktur“ pflegt die Filterebenen und Abschnitte direkt in der
Tabelle: Werte ändern und in der Zeile „Speichern“ klicken. Am Telefon
steht jede Zeile als Karte untereinander.

- **Bereiche** — oberste Filterebene (Reihenfolge über die Zahl)
- **Abteilungen** — je Bereich, zweite Filterebene
- **Kategorien** — Abschnitte der Startseite; Applikationen ohne
  Kategorie stehen unter „Weitere“

Für jeden Eintrag lässt sich ein englischer Anzeigename pflegen. Beim
Löschen bleiben Applikationen erhalten – sie verlieren nur die
Zuordnung. Ein gelöschter Bereich nimmt seine Abteilungen mit.

## Hilfe
**Hilfe** unten im Footer (nur angemeldet) zeigt eine Kurzanleitung,
Version, Lizenz, diese Handbücher, die Änderungshistorie und den Weg zum
Support.

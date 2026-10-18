# LinkHub mit Docker installieren

*English version: [en/docker.md](en/docker.md)*

Diese Anleitung gilt für jeden Rechner mit Docker: einen Linux-Server, einen
Mini-PC oder – zum Ausprobieren – Docker Desktop auf Mac und Windows. Für ein
Synology- oder QNAP-NAS gibt es eine eigene Anleitung: [synology.md](synology.md).

LinkHub läuft in zwei Containern aus demselben Abbild:

| Container | Aufgabe |
|---|---|
| `linkhub` | Weboberfläche auf Port **7280** |
| `linkhub-scheduler` | zeitgesteuerte Aufgaben: nächtliche Sicherung |

## Voraussetzungen

- Docker 24 oder neuer mit Docker Compose (`docker compose version` zeigt die Version)
- mindestens 1 GB freier Arbeitsspeicher und 2 GB Speicherplatz
- Internetzugang **nur beim Bauen** des Abbilds (Composer lädt Laravel und
  weitere Pakete). Im Betrieb braucht LinkHub kein Internet.
- für den Normalbetrieb: Zugriff auf einen Domain Controller (LDAP, besser LDAPS)

## 1. Dateien holen

```bash
git clone https://github.com/steffenmai-maidigital/linkhub.git
cd linkhub
mkdir -p backups
```

Ohne Git: auf GitHub **Code → Download ZIP** wählen, das ZIP entpacken, in den
Ordner wechseln und darin den Ordner `backups` anlegen. In diesem Ordner
landen die nächtlichen Sicherungen.

## 2. Zum Ausprobieren starten

Im Auslieferungszustand läuft LinkHub im **Demo-Modus**: ohne Active Directory,
mit Beispieldaten und festen Demo-Konten. Das Portal selbst ist ohne Anmeldung nutzbar; angemeldet wird nur zur Pflege.

```bash
docker compose up -d --build
docker compose logs -f app
```

Der erste Start baut das Abbild und dauert einige Minuten. Fertig ist er,
wenn im Protokoll `resuming normal operations` erscheint (Protokoll mit **Strg+C** verlassen). Dann im Browser öffnen:
<http://localhost:7280> bzw. `http://<Server-IP>:7280`.

Demo-Konten (Anmeldung oben rechts):

| Konto | Passwort | Rolle |
|---|---|---|
| `admin` | `admin` | Administrator – Einstellungen, Audit-Log, Backup |
| `manager` | `manager` | Manager – pflegt Anwendungen und Struktur |

## 3. Für den Betrieb einrichten

### 3.1 Compose-Datei anpassen

Alle Einstellungen stehen in der `docker-compose.yml` beim Dienst `app`. Eine
`.env`-Datei ist nicht nötig; der `scheduler` übernimmt die Werte automatisch.

| Einstellung | Was eintragen |
|---|---|
| `DEMO_MODE` | `"false"` |
| `APP_URL` | die Adresse, unter der LinkHub später erreichbar ist, z. B. `https://links.firma.de` – ohne Schrägstrich am Ende |
| `LDAP_HOST` | Name des Domain Controllers, z. B. `dc01.firma.local` |
| `LDAP_PORT` und `LDAP_SSL` | `636` und `"true"` für LDAPS (empfohlen) – oder `389` mit `LDAP_TLS: "true"` |
| `LDAP_BASE_DN` | Suchbasis, z. B. `DC=firma,DC=local` |
| `LDAP_USERNAME`, `LDAP_PASSWORD` | ein Servicekonto, das im AD nur **lesen** darf |
| `AD_GROUP_ADMINS` | **Name** der Gruppe der Administratoren, z. B. `LinkHub-Admins` (kein DN) |
| `AD_GROUP_MANAGERS` | **Name** der Gruppe der Manager, z. B. `LinkHub-Manager` |
| Port `"7280:80"` | links einen anderen Port eintragen, falls 7280 belegt ist |

Die vollständige Liste aller Einstellungen steht in der
[README](../README.md#einstellungen-umgebungsvariablen).

Den Schlüssel `APP_KEY` muss niemand mehr von Hand erzeugen: LinkHub legt ihn
beim ersten Start an und speichert ihn im Datenvolume unter `/data/.app_key`.

### 3.2 Neu starten

```bash
docker compose up -d
```

Wer vorher im Demo-Modus getestet hat und mit einer leeren Datenbank beginnen
möchte, setzt für **einen** Start `FRESH_DB: "1"` und danach sofort wieder
`FRESH_DB: "0"` – sonst wird die Datenbank bei jedem Start gelöscht.

### 3.3 In der Oberfläche einrichten

1. Oben rechts mit einem Konto aus der Administratoren-Gruppe anmelden.
   Benutzerkonten entstehen beim ersten Anmelden automatisch; die Rollen
   kommen bei jeder Anmeldung aus den AD-Gruppen (nur direkte
   Mitgliedschaften).
2. **Einstellungen** öffnen: Firmenname, Logo und Farben, die beiden
   AD-Gruppen und die Aufbewahrung der Sicherungen.
3. **Struktur:** Bereiche, Abteilungen und Kategorien anlegen.
4. **Applikationen:** die Anwendungen mit Link, kurzer Beschreibung und
   optionalem Handbuch-Link eintragen und Bereichen oder Abteilungen
   zuordnen. Ohne Zuordnung erscheint eine Anwendung nur unter „Alle“.
5. Im Portal (ohne Anmeldung) prüfen, ob Filter und Suche passen.

## 4. HTTPS einrichten

LinkHub spricht im Container nur HTTP. Für den Betrieb gehört ein Reverse Proxy
mit Zertifikat davor – zum Beispiel nginx, Nginx Proxy Manager, Caddy oder der
Reverse Proxy der Synology. Wie das geht und worauf es ankommt, steht in
[reverse-proxy.md](reverse-proxy.md).

Den Port 7280 nicht im Router oder in der Firewall nach außen freigeben;
erreichbar sein soll nur der Reverse Proxy.

## 5. Prüfliste vor dem Produktivbetrieb

- [ ] `DEMO_MODE: "false"` und `APP_DEBUG: "false"`
- [ ] Anmeldung mit einem echten AD-Konto klappt, Rollen stimmen
- [ ] LinkHub ist nur über HTTPS erreichbar, `APP_URL` beginnt mit `https://`
- [ ] `TRUSTED_PROXIES` enthält die Adresse des Reverse Proxys (Standard: interne Netze)
- [ ] im Ordner `backups` liegt nach der ersten Nacht eine Sicherung
- [ ] der Ordner `backups` und die `docker-compose.yml` werden zusätzlich gesichert (z. B. auf ein zweites Gerät)
- [ ] Impressum und Datenschutz sind unter **Einstellungen** eingetragen, falls nötig

## Ausprobieren ohne Compose

Für einen schnellen Blick genügt ein einzelner Container. Alle Daten gehen beim
Beenden verloren.

```bash
docker build -t linkhub:dev .
docker run --rm -p 7280:80 -e DEMO_MODE=true linkhub:dev
```

## Häufige Probleme

| Problem | Lösung |
|---|---|
| Der Bau bricht ab | Meist fehlte kurz das Internet beim Laden der Pakete. `docker compose up -d --build` einfach noch einmal ausführen. |
| Der Container startet nicht, im Protokoll steht „Downgrade abgelehnt“ | Die Datenbank stammt von einer neueren Version als das Abbild. Siehe [betrieb.md](betrieb.md#zurück-zu-einer-älteren-version). |
| „Server Error“ im Browser | Kurz `APP_DEBUG: "true"` setzen, `docker compose up -d`, Fehlermeldung lesen und danach unbedingt wieder `"false"` setzen. |
| Seite ohne Gestaltung (nur Text) | `APP_URL` passt nicht zur aufgerufenen Adresse, siehe [reverse-proxy.md](reverse-proxy.md). |
| Anmeldung schlägt fehl | Mit `LDAP_LOGGING: "true"` stehen die LDAP-Meldungen im Protokoll (`docker compose logs app`). Häufige Ursachen: falscher Basis-DN, Servicekonto gesperrt, Zertifikat des Domain Controllers wird nicht vertraut. |
| „Zu viele Fehlversuche für diesen Benutzernamen“ | Nach 10 falschen Passwörtern ist der Name 30 Minuten gesperrt. Abwarten – oder den Zwischenspeicher leeren: `docker exec linkhub php artisan cache:clear`. |

# LinkHub betreiben: Update, Sicherung, Wiederherstellung, Notfälle

*English version: [en/operations.md](en/operations.md)*

Gilt für Docker allgemein und für Synology/QNAP. Per SSH auf dem NAS jeweils
`sudo` voranstellen. Die Beispiele gehen davon aus, dass das Compose-Projekt
`linkhub` heißt und im Ordner `linkhub` liegt – dann heißen die Volumes
`linkhub_app-data` usw. (`docker volume ls` zeigt die tatsächlichen Namen).

## Was liegt wo?

| Ort | Inhalt |
|---|---|
| Volume `linkhub_app-data` (`/data`) | Datenbank `linkhub.sqlite`, Schlüssel `.app_key`, Sicherungen vor jedem Update unter `backups/` |
| Volume `linkhub_app-storage` | Sitzungen, Zwischenspeicher, Protokolle |
| Volume `linkhub_app-uploads` | Firmenlogo |
| Ordner `./backups` | nächtliche Sicherungen; `manual/` für Sicherungen, die von Hand angelegt wurden |
| `docker-compose.yml` | alle Einstellungen, auch Zugangsdaten für AD |

Die Compose-Datei enthält Zugangsdaten. Sie gehört in die Sicherung, aber
nicht in einen Ordner, den viele lesen können.

## Update

**Docker:**

```bash
cd linkhub
git pull                     # oder neue Dateien über den Ordner kopieren
docker compose up -d --build
docker compose logs -f app
```

**Synology Container Manager:** die eigene `docker-compose.yml` sichern →
Projekt stoppen → neue Dateien über die alten entpacken →
`docker-compose.yml` zurückkopieren → **Aktion → Bereinigen und neu
erstellen**.

Vor dem Update im `CHANGELOG.md` nachsehen, ob unter **Geändert** etwas zu tun
ist (z. B. ein neuer Ordner oder eine neue Einstellung).

### Was beim Start passiert

Der App-Container führt bei jedem Start `php artisan app:upgrade` aus:

| Schritt | Wirkung |
|---|---|
| Versionsabgleich | Die Version in der Datenbank wird mit der Version des Abbilds verglichen. Ist die Datenbank neuer (**Downgrade**), startet der Container nicht – es wird nichts verändert. |
| Sicherung | Vor jeder Änderung landet eine Kopie der Datenbank in `/data/backups/` (die 10 neuesten bleiben). |
| Migrationen | Neue Tabellen und Spalten werden angelegt. |
| Umbauten | Versionsabhängige Datenumbauten laufen genau einmal. |
| Vermerk | Version, Build und Zeitpunkt stehen danach unter **Hilfe** und im Protokoll. |

### Versionen

Versionsnummern folgen dem Schema **JJMMTTNN**: Datum plus laufende Nummer
des Tages (`26101701` = erste Version am 17.10.2026). Jeder Bau heißt
`linkhub:latest`, veröffentlichte Versionen tragen zusätzlich ihre Nummer.
Vorhandene Abbilder zeigt:

```bash
docker image ls linkhub
```

### Zurück zu einer älteren Version

Weil LinkHub kein Downgrade zulässt, braucht der Rückweg auch die Datenbank von
vorher:

1. Container stoppen: `docker compose down`
2. Die Sicherung von vor dem Update suchen und zurückspielen:

   ```bash
   docker run --rm -v linkhub_app-data:/data alpine ls -1 /data/backups
   docker run --rm -v linkhub_app-data:/data alpine sh -c \
     'cp /data/backups/DATEI.sqlite /data/linkhub.sqlite && rm -f /data/linkhub.sqlite-wal /data/linkhub.sqlite-shm'
   ```

3. In der `docker-compose.yml` bei **beiden** Diensten `build: .`
   auskommentieren (`# build: .`) und bei `image:` die alte Nummer eintragen,
   z. B. `image: linkhub:26101701`.
4. `docker compose up -d`

Alte Abbilder deshalb nicht vorschnell löschen (`docker image prune -a`
entfernt sie).

## Sicherung

- **Automatisch:** jede Nacht um 1:30 Uhr legt der Container
  `linkhub-scheduler` ein ZIP mit Datenbank und Uploads samt SHA-256-Prüfsumme
  in `./backups` ab. Wie lange sie aufbewahrt werden, stellt der Administrator unter **Einstellungen → Backup** ein (mindestens 4 Tage).
- **Sofort, im Browser:** als Administrator unter **Einstellungen → Backup jetzt erstellen**.
  Diese Sicherungen liegen in `./backups/manual` und werden nie automatisch
  gelöscht.
- **Zusätzlich auslagern:** den Ordner `backups` und die `docker-compose.yml`
  regelmäßig auf ein zweites Gerät kopieren, auf der Synology z. B. mit
  **Hyper Backup**.

Eine Sicherung prüfen:

```bash
cd backups && sha256sum -c DATEI.zip.sha256
```

## Wiederherstellung

`DATEI.zip` durch den Namen der Sicherung ersetzen (bei Sicherungen von Hand:
`manual/DATEI.zip`).

```bash
cd linkhub
docker compose stop

docker run --rm -v linkhub_app-data:/data -v linkhub_app-uploads:/uploads \
  -v "$PWD/backups:/backups:ro" alpine sh -c "
  set -e; mkdir /tmp/r && cd /tmp/r && unzip -q /backups/DATEI.zip
  rm -f /data/linkhub.sqlite /data/linkhub.sqlite-wal /data/linkhub.sqlite-shm
  cp database/linkhub.sqlite /data/linkhub.sqlite
  if [ -d uploads ]; then cp -r uploads/. /uploads/; fi
  echo wiederhergestellt"

docker compose start
```

Die Dateirechte setzt LinkHub beim Start selbst.

**Umzug auf einen anderen Server:** Dort LinkHub wie in der
Installationsanleitung einrichten (gleiche `docker-compose.yml`), einmal
starten und dann die Wiederherstellung wie oben ausführen. Danach `APP_URL`
und – falls genutzt – die Adressen in den Einstellungen prüfen.

## Testsystem

`docker-compose.test.yml` startet eine vollständig getrennte zweite Instanz
auf Port **7281** – mit eigenen Volumes, eigenem Ordner `backups-test` und
eigenem Abbild `linkhub:test`. Die Produktion wird dabei nicht berührt.

```bash
mkdir -p backups-test
docker compose -p linkhub-test -f docker-compose.test.yml up -d --build
```

Synology: `docker-compose.test.yml` als eigenes Projekt `linkhub-test` im
selben Ordner anlegen.

Das Testsystem zeigt oben ein rotes Banner **TESTSYSTEM**. Der Sicherungsordner
der Produktion ist dort nur lesend eingebunden: Unter **Einstellungen →
Testsystem** spielt der Administrator den letzten Produktionsstand per Klick
ein; die Prüfsumme wird vorher geprüft.

## Notfälle

### Kein Administrator kommt hinein

Die Administratoren-Gruppe steht unter **Einstellungen**, ersatzweise in
`AD_GROUP_ADMINS`. Prüfen, ob das Konto **direktes** Mitglied dieser Gruppe ist
(eingetragen wird nur der Gruppenname, z. B. `LinkHub-Admins`; verschachtelte Gruppen werden nicht ausgewertet). Die LDAP-Meldungen zeigt `LDAP_LOGGING: "true"` im
Protokoll (`docker compose logs -f app`).

### Ein Benutzername ist gesperrt

Nach 10 falschen Passwörtern ist ein Benutzername 30 Minuten gesperrt. Sofort
aufheben:

```bash
docker exec linkhub php artisan cache:clear
```

### Seite ohne Gestaltung oder „Zu viele Weiterleitungen“

Siehe [reverse-proxy.md](reverse-proxy.md#häufige-fehler).

### Fehlersuche

- Container-Protokoll: `docker compose logs -f app` (Synology: **Projekt → Protokoll**)
- Laravel-Protokoll: `docker exec linkhub tail -n 100 storage/logs/laravel.log`
- Für eine genaue Fehlermeldung im Browser kurz `APP_DEBUG: "true"` setzen,
  neu starten und danach **unbedingt** wieder `"false"` setzen.

### Alles zurücksetzen

`FRESH_DB: "1"` in der `docker-compose.yml` löscht beim nächsten Start die
Datenbank. Danach sofort wieder auf `"0"` stellen – sonst passiert es bei
jedem Start erneut.

## Wiederkehrende Aufgaben

| Wann | Was |
|---|---|
| monatlich | Blick in den Ordner `backups` (liegt jede Nacht eine neue Sicherung?) und unter **Hilfe** |
| vor jedem Update | **Backup jetzt erstellen**, `CHANGELOG.md` lesen, Update erst im Testsystem probieren |
| vierteljährlich | Links im Portal prüfen: gibt es Anwendungen, die nicht mehr existieren? |

## Nützliche Befehle

Jeweils mit `docker exec linkhub` davor:

| Befehl | Zweck |
|---|---|
| `php artisan app:upgrade` | Versionsabgleich und Migrationen von Hand ausführen |
| `php artisan schedule:list` | geplante Aufgaben und nächste Ausführung anzeigen |
| `php artisan cache:clear` | Zwischenspeicher leeren (hebt auch Anmeldesperren auf) |

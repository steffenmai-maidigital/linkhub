# Renaming to English identifiers (standard 10)

In October 2026 every German identifier in LinkHub was renamed to English
(classes, methods, variables, routes, views, CSS/JS names, database columns,
stored values, settings keys, commands, files). The user interface did not
change: German UI strings stay the translation keys, English lives in
`lang/en.json`, the manuals under `docs/` stay German.

This table is for people with an **old fork, old scripts or an old backup**.
Existing installations need nothing: the migration
`database/migrations/0003_01_01_000200_rename_to_english.php` converts the
database at start-up (idempotent, also for restored old backups), old page
addresses redirect permanently (`routes/legacy.php`), old filter bookmarks of
the portal (`/?bereich=…&abteilung=…`) keep working, and the old artisan
command name is an alias. The audit log (`activity_log`) is a SHA-256 hash
chain and is **never** rewritten — its stored module names and descriptions
stay German.

Shared building blocks (Directory, Mfa, TwoFactor, Fox, LegalPages,
ProductInfo, ErrorPage) use the same
names in CrewGate, LinkHub and BilDaBooky.

## Files and folders

| old | new |
|---|---|
| `app/Support/Verzeichnis.php` | `app/Support/Directory.php` |
| `app/Console/Commands/VerzeichnisExtern.php` | `app/Console/Commands/DirectoryIsolated.php` |
| `app/Support/Fuchs.php` | `app/Support/Fox.php` |
| `app/Support/Rechtliches.php` | `app/Support/LegalPages.php` |
| `app/Support/Produkt.php` | `app/Support/ProductInfo.php` |
| `app/Support/Fehlerseite.php` | `app/Support/ErrorPage.php` |
| `app/Models/Concerns/ZweiFaktor.php` | `app/Models/Concerns/TwoFactor.php` |
| `app/Console/Commands/AdminPasswort.php` | `app/Console/Commands/AdminPassword.php` |
| `app/Http/Middleware/FuchsAnlass.php` | `app/Http/Middleware/FoxOccasion.php` |
| `config/produkt.php` | `config/product.php` |
| — | `routes/legacy.php` (old addresses) |
| — | `database/migrations/0003_01_01_000200_rename_to_english.php` |
| `resources/views/hilfe.blade.php`, `hilfe-doc.blade.php` | `help.blade.php`, `help-doc.blade.php` |
| `resources/views/konto.blade.php` | `account.blade.php` |
| `resources/views/struktur/index.blade.php` | `structure/index.blade.php` |
| `resources/views/partials/fuchs.blade.php` | `partials/fox.blade.php` |
| `resources/views/partials/rechtliches-einstellungen.blade.php` | `partials/legal-settings.blade.php` |
| `resources/views/partials/rechtliches-links.blade.php` | `partials/legal-links.blade.php` |
| `resources/views/rechtliches/seite.blade.php` | `legal/page.blade.php` |
| `public/js/fuchs.js`, `public/js/symbole.js` | `public/js/fox.js`, `public/js/symbols.js` |
| `docs/BENUTZERHANDBUCH.md` | `docs/USER-GUIDE.md` |
| `docs/ADMIN-HANDBUCH.md` | `docs/ADMIN-GUIDE.md` |
| `docs/ARCHITEKTUR.md` | `docs/ARCHITECTURE.md` |
| `docs/SICHERHEIT.md` | `docs/SECURITY.md` |
| `docs/DOKUMENTATION.md` | `docs/DOCUMENTATION.md` |

## Classes, constants, methods

| old | new |
|---|---|
| `Verzeichnis::SCHLUESSEL`, `VERSCHLUESSELUNG`, `GRUPPEN_KLASSEN`, `STANDARD_FILTER` | `Directory::KEYS`, `ENCRYPTION`, `GROUP_CLASSES`, `DEFAULT_FILTER` |
| `einstellungen()`, `aktiv()`, `ausEinstellungen()`, `speichern()`, `regeln()` | `settings()`, `enabled()`, `fromSettings()`, `save()`, `rules()` |
| `anmelden()`, `pruefen()`, `finden()`, `suchen()`, `gruppen()`, `mitglieder()`, `gehoertZu()` | `authenticate()`, `test()`, `find()`, `search()`, `groups()`, `members()`, `isMemberOf()` |
| `normalisiereDn()`, `externAusfuehren()`, `$tlsImProzess` (private helpers as in CrewGate) | `normaliseDn()`, `handleIsolated()`, `$processTls` |
| directory entry keys `vorname`, `nachname`, `gruppen` | `first_name`, `last_name`, `groups` |
| `pruefen()` result `modus` (`dienst`/`vorlage`), `eintraege`, `millisekunden` | `test()` result `mode` (`service`/`template`), `entries`, `milliseconds` |
| `config('ldap.*')` `aktiv`, `verschluesselung`, `bind_passwort`, `bind_vorlage`, `benutzer_attribut`, `benutzer_filter`, `zertifikat` | `enabled`, `encryption`, `bind_password`, `bind_template`, `user_attribute`, `user_filter`, `certificate` |
| `config('produkt.*')` `quellcode`, `projektseite` (`name`, `symbol`, `github` unchanged) | `config('product.*')` `source_url`, `project_url` |
| `Mfa::PFLICHT`, `OPTIONAL`, `AUS`, `STUFEN`, `RANG` | `Mfa::REQUIRED`, `OPTIONAL`, `OFF`, `LEVELS`, `RANK` |
| `Mfa::gruppen()`, `stufe()`, `pflichtGruppen()`, `richtlinie()`, `erforderlich()`, `erlaubt()`, `titel()` | `groups()`, `level()`, `requiredGroups()`, `policy()`, `isRequired()`, `isAllowed()`, `label()` |
| Mfa group keys `admin`, `manager`, `sonst` | `admin`, `manager`, `other` |
| trait `ZweiFaktor` | trait `TwoFactor` |
| `User::LOKAL`, `VERZEICHNIS` | `User::LOCAL`, `DIRECTORY` (values `local`, `ldap` unchanged) |
| `istLokal()`, `herkunft()`, `zeigtFuchs()`, `rechteAusGruppen()`, `startPasswort()` | `isLocal()`, `origin()`, `showsFox()`, `permissionsFromGroups()`, `initialPassword()` |
| `Fuchs::sichtbar()`, `anzeige()`, `gast()`, `daten()`, private `tagesgruss()`, `paket()`, `fehlerSatz()` | `Fox::visible()`, `display()`, `guest()`, `data()`, `greeting()`, `package()`, `errorLine()` |
| `FuchsAnlass::ERNST`, `ernst()` | `FoxOccasion::SERIOUS`, `serious()` |
| `Rechtliches::ARTEN`, `aktiv()`, `titel()`, `speichern()`, `vorlageDatenschutz()` | `LegalPages::TYPES`, `enabled()`, `title()`, `save()`, `privacyTemplate()` (`text()`, `link()`, `url()`, `links()`, `html()` unchanged) |
| `LegalPages::links()` keys `titel`, `extern` | `title`, `external` |
| `Produkt::quellcode()`, `projektseite()` | `ProductInfo::sourceUrl()`, `projectUrl()` |
| `Fehlerseite::referenz()`, `grund()`, `protokolliert()`, `wartezeit()`, `uploadGrenze()`, `angemeldet()`, `unbekannteAdresse()` | `ErrorPage::reference()`, `reason()`, `isLogged()`, `retryAfter()`, `uploadLimit()`, `signedIn()`, `unknownAddress()` |
| `ErrorLog::recent()` keys `zeilen`, `anzahl` | `lines`, `count` |
| `AuthController::anmelden()`, `verzeichnisKonto()` (private) | `signIn()`, `directoryUser()` |
| `AccountController::richtlinie()`, `passwortOk()` (private) | `policy()`, `passwordOk()` |
| `LegalController::impressum()`, `datenschutz()`, `zeigen()` | `imprint()`, `privacy()`, `show()` |
| `HelpController::DOCS` keys `benutzer`, `architektur`, `sicherheit`, `ueberblick` | `user`, `architecture`, `security`, `overview` (+ `LEGACY_DOCS` for old links) |
| `StructureController::TYPES` `bereiche`, `abteilungen`, `kategorien` | `areas`, `departments`, `categories` |
| `UpgradeService::hatBestand()` | `hasData()` |
| fallback slug `eintrag` (names without letters) | `entry` (existing slugs unchanged) |
| local variables (`$eintrag`, `$gruppen`, `$richtlinie`, `$passwort`, `$bremse`, `$ergebnis`, `$pfad`, `$datei`, …) | English equivalents (`$entry`, `$groups`, `$policy`, `$password`, `$throttle`, `$result`, `$path`, `$file`, …) |

## Database: columns and stored values

| old | new |
|---|---|
| column `users.fuchs` | `users.fox` |
| `users.mfa_policy` `pflicht` / `aus` | `required` / `off` (`optional`, NULL unchanged) |
| `activity_log` (log name, description, properties) | **unchanged** (hash chain) |
| properties of **new** audit entries: rename `alt`/`neu`, sign-in `gruppen` | `old`/`new`, `groups` (older entries keep their keys) |

## Settings keys (table `settings`)

| old | new |
|---|---|
| `fuchs_aktiv` | `fox_enabled` |
| `ldap_aktiv`, `ldap_verschluesselung`, `ldap_bind_passwort`, `ldap_bind_vorlage` | `ldap_enabled`, `ldap_encryption`, `ldap_bind_password`, `ldap_bind_template` |
| `ldap_benutzer_attribut`, `ldap_benutzer_filter`, `ldap_zertifikat` | `ldap_user_attribute`, `ldap_user_filter`, `ldap_certificate` |
| `mfa_gruppen_pflicht`, `mfa_sonst` | `mfa_required_groups`, `mfa_other` (`mfa_admin`, `mfa_manager` unchanged) |
| values of `mfa_<group>` `pflicht` / `aus` | `required` / `off` |
| `impressum_aktiv`, `_art`, `_text`, `_link` | `imprint_enabled`, `_type`, `_text`, `_url` |
| `datenschutz_aktiv`, `_art`, `_text`, `_link` | `privacy_enabled`, `_type`, `_text`, `_url` |
| `ldap_host`, `ldap_port`, `ldap_base_dn`, `ldap_bind_dn`, `ldap_timeout`, `ldap_ca_certificate`, `ad_group_*`, `brand_*`, `license_*`, `backup_retention_days`, `app_*` | unchanged |

Form fields of the settings page use the same new names.

## Session, cache, browser storage

| old | new |
|---|---|
| session `mfa_richtlinie`, `mfa_offen` | `mfa_policy`, `mfa_pending` (old sessions: sign in once more) |
| session `login.2fa` keys `bis`, `gruppen`, `richtlinie` | `until`, `groups`, `policy` |
| flash `zugang` | `credentials` |
| flash `fuchs_anlass` (`ernst`/`normal`) | `fox_occasion` (`serious`/`normal`) |
| rate limiter `anmeldung:<name>` | `sign-in:<name>` |
| localStorage `lh-fuchs-tiefschlaf`, `lh-fuchs-zuletzt` | `lh-fox-sleep`, `lh-fox-last` (old values are taken over once and deleted) |
| localStorage `lh-fuchs-lage` (fox position) | `lh-fox-position` (taken over once, old key removed) |
| log context `referenz` | `reference` |

## Addresses (GET redirects 301, query kept)

| old | new | route name old → new |
|---|---|---|
| `/sprache/{locale}` | `/locale/{locale}` | `locale.switch` |
| `/impressum`, `/datenschutz` | `/imprint`, `/privacy` | `impressum`, `datenschutz` → `imprint`, `privacy` |
| `/hilfe` | `/help` | `hilfe` → `help` |
| `/hilfe/handbuch/{doc}` (`benutzer`, `architektur`, `sicherheit`, `ueberblick`) | `/help/manual/{doc}` (`user`, `architecture`, `security`, `overview`) | `hilfe.doc` → `help.doc` |
| `/hilfe/protokoll` (DELETE) | `/help/log` | `hilfe.protokoll` → `help.clear-log` |
| `/konto` | `/account` | `konto` → `account` |
| `/konto/passwort`, `/konto/fuchs` (PUT/POST) | `/account/password`, `/account/fox` | `konto.passwort`, `konto.fuchs` → `account.password`, `account.fox` |
| `/konto/2fa`, `/konto/2fa/bestaetigen`, `/konto/2fa/codes` (POST/DELETE) | `/account/2fa`, `/account/2fa/confirm`, `/account/2fa/codes` | `konto.2fa.start/bestaetigen/codes/aus` → `account.2fa.start/confirm/codes/disable` |
| `/applikationen`, `/applikationen/create`, `/applikationen/{application}/edit` | `/applications`, `/applications/create`, `/applications/{application}/edit` | `applications.*` (unchanged) |
| `/struktur`, `/struktur/{type}/…` (types `bereiche`, `abteilungen`, `kategorien`) | `/structure`, `/structure/{type}/…` (types `areas`, `departments`, `categories`) | `struktur.*` → `structure.*` |
| `/einstellungen`, `/einstellungen/verzeichnis-testen` (POST) | `/settings`, `/settings/test-directory` | `settings.*` (unchanged) |
| `/benutzer`, `/benutzer/{user}`, `…/passwort`, `…/2fa` | `/users`, `/users/{user}`, `…/password`, `…/2fa` | `users.*` (unchanged) |
| `/audit/pruefen` (POST) | `/audit/verify` | `audit.verify` |
| `/backup/jetzt` (POST) | `/backup/now` | `backup.now` |
| `/testsystem/restore` (POST) | `/test-system/restore` | `testsystem.restore` → `test-system.restore` |

Only GET addresses redirect; forms always come from the new pages.
Query parameters (old names are still read): portal `?bereich=` → `?area=`,
`?abteilung=` → `?department=`; user list `?suche=` → `?search=`,
`?bearbeiten=` → `?edit=`.

## Views, sections, CSS, JS

| old | new |
|---|---|
| Blade sections of error pages `bild`, `titel`, `tipps`, `neu_laden` | `symbol`, `title`, `tips`, `reload` |
| `@include` parameters `angemeldet`, `nummer`, `fuchsPrefix` (`lh-fuchs`) | `signedIn`, `number`, `foxPrefix` (`lh-fox`) |
| view variables `verzeichnis`, `konto`, `istAdmin`, `fehler`, `zugang`, `bearbeiten`, `richtlinie`, `mfaOffen`, `titel` | `directory`, `account`, `isAdmin`, `recentErrors`, `credentials`, `editing`, `policy`, `mfaPending`, `title` |
| CSS `.fuchs`, `.fuchs-blase`, `-blase-knoepfe`, `-figur`, `-text`, `-link`, `-weiter`, `-zu`, `-schlafen` | `.fox`, `.fox-bubble`, `-bubble-buttons`, `-figure`, `-text`, `-link`, `-next`, `-close`, `-sleep` |
| fox states `.da`, `.spricht`, `.winkt`, `.huepft`, `.schlaeft`, `.tiefschlaf`, `.im-anmeldung`, `.im-seite`, `.links`, `.oben`, `.zieht` | `.shown`, `.speaking`, `.waving`, `.hopping`, `.asleep`, `.deep-sleep`, `.on-login`, `.on-page`, `.at-left`, `.at-top`, `.dragging` |
| fox drawing `.f-schwanz`, `-koerper`, `-pfote`, `-kopf`, `-ohr-l/-r`, `-augen`, `-schlafaugen`, `-mund`, `-umhang` | `.f-tail`, `-body`, `-paw`, `-head`, `-ear-l/-r`, `-eyes`, `-sleep-eyes`, `-mouth`, `-cloak` |
| keyframes `f-wedeln`, `f-atmen`, `f-kopf`, `f-blinzeln`, `f-ohr`, `f-winken`, `f-huepf`, `f-schlafatmen`, `blase-rein` | `f-wag`, `f-breathe`, `f-head`, `f-blink`, `f-ear`, `f-wave`, `f-hop`, `f-sleep-breathe`, `bubble-in` |
| `id="fuchs"`, `data-fuchs`, `data-ohne-fuchs` (form) | `id="fox"`, `data-fox`, `data-no-fox` |
| fox data keys `gruss`, `kontext`, `tipps`, `sprueche`, `lob`, `buch`, `fehler`, `warten`, `texte` (`schliessen`, `schlafen`, `wecken`, `aufgewacht`, `weiter`, `fuchs`) | `greeting`, `context`, `tips`, `sayings`, `praise`, `book`, `error`, `waiting`, `texts` (`close`, `sleep`, `wake`, `woken`, `next`, `fox`) |
| `.fehlerseite`, `.fehler-bild`, `-code`, `-grund`, `-logo`, `-referenz`, `-text`, `-tipps` | `.error-page`, `.error-symbol`, `-code`, `-reason`, `-logo`, `-reference`, `-text`, `-tips` |
| `.rechtliches`, `.rechtliches-einstellung`, `fieldset.rechtlich`, `.rechtlich-wahl`, `-text`, `-link`, `.login-rechtliches`, `data-art` | `.legal-page`, `.legal-settings`, `fieldset.legal`, `.legal-choice`, `-text`, `-url`, `.login-legal`, `data-type` |
| `#vorlage-datenschutz`, `data-vorlage` | `#privacy-template`, `data-template` |
| `.macher`, `.btn.zweit`, `.hilfe-liste`, `.logzeile`, `form.suchleiste` | `.maker`, `.btn.secondary`, `.help-list`, `.log-line`, `form.search-bar` |
| portal tile `.titel`, `.aktionen` | `.title`, `.actions` |
| portal `data-gruppe`, `data-suche`, `#app-suche`, `#keine-treffer` | `data-group`, `data-search`, `#app-search`, `#no-results` |
| symbols `.bw-emoji`, `.bw-symbol`, `.bw-geraet-emoji`, `.keine-symbole`, `.luft-links`, `.luft-rechts` | `.emoji-image`, `.emoji-symbol`, `.device-emoji`, `.no-symbols`, `.space-left`, `.space-right` |
| `<meta name="symbol-basis">`, `window.BWSymbole.tauschen()` | `<meta name="symbol-base">` (old name still read), `window.Symbols.swap()` |
| user list form field `suche` | `search` |

## Commands and tools

| old | new |
|---|---|
| `php artisan linkhub:admin-passwort {benutzername}` | `linkhub:admin-password {username}` (old name is an alias) |
| `php artisan verzeichnis:extern` | `directory:isolated` (internal, no alias) |
| GitHub workflow input `veroeffentlichen`, job `abbild` | `publish`, `image` |
| entrypoint function `rechte`, variable `ALT_KEY` | `permissions`, `OLD_KEY` |

## What stays German on purpose

- UI strings (`__('…')` keys, `lang/en.json` keys, placeholders like
  `:fehler` or `:minuten` inside them), manuals in `docs/`, CHANGELOG entries,
  the German half of comments, plain German messages that are shown to users
  (e.g. errors of the test system restore).
- Audit log module names and descriptions (`Benutzer`, `Einstellungen`,
  `Konto geändert`, …) — hash chain; new entries are written the same way.
- Demo and test data (area, department, category and application names, the
  German OU/group/user names of the AD test fixture), slugs derived from them.
- Legacy aliases listed above (old URLs, query parameters, command name, old
  keys in the migration and in `routes/legacy.php`).

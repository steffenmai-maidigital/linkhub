@extends('layouts.app')
@section('title', __('Einstellungen'))
@section('content')
    <h1>{{ __('Einstellungen') }}</h1>

    <form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        {{-- ===== 1. Firma & Erscheinungsbild / Company & appearance ===== --}}
        <div class="panel">
            <h2 style="margin-top:0">1 · {{ __('Firma & Erscheinungsbild') }}</h2>
            <div class="row">
                <div><label for="brand_company_name">{{ __('Firmenname (im Header, ersetzt „LinkHub“)') }}</label><input type="text" id="brand_company_name" name="brand_company_name" value="{{ old('brand_company_name', $settings['brand_company_name']) }}" placeholder="Musterfirma GmbH"></div>
            </div>
            <label for="logo">{{ __('Firmenlogo (PNG, JPG, SVG oder WebP, max. 2 MB)') }}</label>
            @if($settings['brand_logo_path'])
                <p><img src="{{ asset($settings['brand_logo_path']) }}" alt="{{ __('Aktuelles Logo') }}" class="logo-preview"></p>
                <label class="choice"><input type="checkbox" name="remove_logo" value="1"> {{ __('Logo entfernen') }}</label>
            @endif
            <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.svg,.webp">
            <div class="row" style="margin-top:.6rem">
                <div style="max-width:200px"><label for="brand_header_color">{{ __('Header-Farbe') }}</label><input type="color" id="brand_header_color" name="brand_header_color" value="{{ old('brand_header_color', $settings['brand_header_color'] ?: '#14557b') }}"></div>
                <div style="max-width:200px"><label for="brand_accent_color">{{ __('Akzentfarbe (Buttons, Links)') }}</label><input type="color" id="brand_accent_color" name="brand_accent_color" value="{{ old('brand_accent_color', $settings['brand_accent_color'] ?: '#14557b') }}"></div>
                <div style="max-width:200px"><label for="brand_footer_color">{{ __('Footer-Farbe') }}</label><input type="color" id="brand_footer_color" name="brand_footer_color" value="{{ old('brand_footer_color', $settings['brand_footer_color'] ?: '#1d2733') }}"></div>
            </div>
            <label for="brand_footer_text">{{ __('Footer-Text') }}</label>
            <input type="text" id="brand_footer_text" name="brand_footer_text" value="{{ old('brand_footer_text', $settings['brand_footer_text']) }}" placeholder="© Musterfirma GmbH · {{ __('Link- und Applikationsportal') }}">
            <p class="hint">{{ __('Die Farben gelten im hellen und dunklen Modus. Der Hell/Dunkel-Umschalter (◐) sitzt oben rechts im Header, daneben der Sprachumschalter Deutsch/Englisch.') }}</p>
        </div>

        {{-- ===== 2. Anmeldung / Sign-in ===== --}}
        <div class="panel">
            <h2 style="margin-top:0">2 · {{ __('Anmeldung') }}</h2>
            <p>{{ __('Das Portal selbst ist ohne Anmeldung nutzbar. Zum Pflegen melden sich Manager und Administratoren an — mit eigenen Konten (Menü „Benutzer“) und zusätzlich, wenn eingeschaltet, mit ihrem Konto aus dem Verzeichnis: Active Directory, Samba AD, OpenLDAP oder Univention.') }}</p>
            @if($demo)
                <div class="flash ok" style="margin-top:.4rem">{{ __('Demo-Modus: Die Anmeldung läuft über die eingebauten Demo-Konten. Ein Verzeichnis wird nicht angesprochen.') }}</div>
            @endif
            <label class="choice" style="font-size:1rem"><input type="checkbox" name="ldap_enabled" value="1" @checked(old('ldap_enabled', $directory['enabled']))> <b>{{ __('Anmeldung über das Verzeichnis einschalten') }}</b></label>
            <div class="row">
                <div><label for="ldap_host">{{ __('Server (Hostname oder IP, mehrere mit Komma)') }}</label><input type="text" id="ldap_host" name="ldap_host" value="{{ old('ldap_host', $directory['host']) }}" placeholder="dc01.firma.local" autocapitalize="none" spellcheck="false"></div>
                <div style="max-width:120px"><label for="ldap_port">{{ __('Port') }}</label><input type="number" id="ldap_port" name="ldap_port" value="{{ old('ldap_port', $directory['port']) }}" min="1" max="65535"></div>
                <div style="max-width:240px">
                    <label for="ldap_encryption">{{ __('Verschlüsselung') }}</label>
                    <select id="ldap_encryption" name="ldap_encryption">
                        @foreach(\App\Support\Directory::ENCRYPTION as $value => $label)
                            <option value="{{ $value }}" @selected(old('ldap_encryption', $directory['encryption']) === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label class="choice"><input type="checkbox" name="ldap_certificate" value="1" @checked(old('ldap_certificate', $directory['certificate']))> {{ __('Zertifikat des Servers prüfen (empfohlen; aus bei selbst signierten Zertifikaten im Testnetz)') }}</label>
            <label for="ldap_ca_certificate">{{ __('Eigene Zertifizierungsstelle (CA-Zertifikat im PEM-Format, optional)') }}</label>
            <textarea id="ldap_ca_certificate" name="ldap_ca_certificate" rows="4" spellcheck="false" autocapitalize="none" placeholder="-----BEGIN CERTIFICATE-----&#10;…&#10;-----END CERTIFICATE-----" style="font-family:monospace;font-size:.8rem">{{ old('ldap_ca_certificate', $directory['ca_certificate']) }}</textarea>
            <p class="hint">{{ __('Nötig, wenn das Verzeichnis ein Zertifikat der eigenen Firmen- oder Schul-CA verwendet. Dann die Prüfung eingeschaltet lassen, statt sie abzuschalten.') }}</p>
            <label for="ldap_base_dn">{{ __('Suchbasis (Base DN)') }}</label>
            <input type="text" id="ldap_base_dn" name="ldap_base_dn" value="{{ old('ldap_base_dn', $directory['base_dn']) }}" placeholder="DC=firma,DC=local" autocapitalize="none" spellcheck="false">
            <div class="row">
                <div><label for="ldap_bind_dn">{{ __('Servicekonto (DN oder Anmeldename, nur lesend)') }}</label><input type="text" id="ldap_bind_dn" name="ldap_bind_dn" value="{{ old('ldap_bind_dn', $directory['bind_dn']) }}" placeholder="CN=svc-linkhub,OU=Service,DC=firma,DC=local" autocomplete="off"></div>
                <div><label for="ldap_bind_password">{{ __('Passwort des Servicekontos') }} <span class="hint">({{ __('leer lassen = unverändert; nach einem Serverwechsel neu eingeben') }})</span></label><input type="password" id="ldap_bind_password" name="ldap_bind_password" value="" autocomplete="new-password"></div>
            </div>
            <details style="margin-top:.6rem">
                <summary style="cursor:pointer;font-size:.9rem">{{ __('Weitere Angaben (meist nicht nötig)') }}</summary>
                <div class="row">
                    <div><label for="ldap_user_attribute">{{ __('Attribut mit dem Benutzernamen') }}</label><input type="text" id="ldap_user_attribute" name="ldap_user_attribute" value="{{ old('ldap_user_attribute', $directory['user_attribute']) }}" placeholder="sAMAccountName"><p class="hint">{{ __('Active Directory: sAMAccountName · OpenLDAP/Univention: uid') }}</p></div>
                    <div><label for="ldap_user_filter">{{ __('Benutzerfilter (LDAP-Filter)') }}</label><input type="text" id="ldap_user_filter" name="ldap_user_filter" value="{{ old('ldap_user_filter', $directory['user_filter']) }}" placeholder="(&(objectClass=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))"><p class="hint">{{ __('Schränkt ein, wer sich anmelden darf, z. B. nur aktive Konten einer OU.') }}</p></div>
                </div>
                <div class="row">
                    <div><label for="ldap_bind_template">{{ __('Ohne Servicekonto: Anmeldename nach Vorlage') }}</label><input type="text" id="ldap_bind_template" name="ldap_bind_template" value="{{ old('ldap_bind_template', $directory['bind_template']) }}" placeholder="%s@firma.local"><p class="hint">{{ __('%s wird durch den eingegebenen Benutzernamen ersetzt, z. B. %s@firma.local oder uid=%s,ou=people,dc=firma,dc=local.') }}</p></div>
                    <div style="max-width:160px"><label for="ldap_timeout">{{ __('Zeitlimit (Sekunden)') }}</label><input type="number" id="ldap_timeout" name="ldap_timeout" value="{{ old('ldap_timeout', $directory['timeout']) }}" min="1" max="60"></div>
                </div>
            </details>
            <p class="hint">{{ __('Nach dem Speichern unten „Verbindung testen“ anklicken. Ein eigenes Admin-Konto bleibt als Notzugang immer möglich (docker exec linkhub php artisan linkhub:admin-password).') }}</p>
            <h3 style="margin:1rem 0 .2rem">🔐 {{ __('Zwei-Faktor-Anmeldung') }}</h3>
            <p class="hint" style="margin-top:0">{{ __('Zusätzlicher Code aus einer Authenticator-App — je Gruppe: Pflicht (muss eingerichtet werden), wählbar (jeder kann) oder aus. Gehört jemand zu mehreren Gruppen, gilt die strengste Stufe. Ausnahmen je Benutzer unter „Benutzer“.') }}</p>
            <div class="row">
                @foreach(\App\Support\Mfa::groups() as $mfaKey => [$mfaLabel])
                    <div style="max-width:260px">
                        <label for="mfa_{{ $mfaKey }}">{{ $mfaLabel }}</label>
                        <select id="mfa_{{ $mfaKey }}" name="mfa_{{ $mfaKey }}">
                            @foreach(\App\Support\Mfa::LEVELS as $level => $levelLabel)
                                <option value="{{ $level }}" @selected(old('mfa_' . $mfaKey, \App\Support\Mfa::level($mfaKey)) === $level)>{{ __($levelLabel) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            <label for="mfa_required_groups">{{ __('Verzeichnisgruppen mit Pflicht (eine je Zeile, DN oder Name)') }}</label>
            <textarea id="mfa_required_groups" name="mfa_required_groups" rows="2" placeholder="Geschaeftsfuehrung&#10;CN=IT-Admins,OU=Gruppen,DC=firma,DC=local" @error('mfa_required_groups') aria-invalid="true" aria-describedby="mfa_required_groups-error" @enderror>{{ old('mfa_required_groups', \App\Models\Setting::get('mfa_required_groups')) }}</textarea>
            @error('mfa_required_groups')<p class="field-error" id="mfa_required_groups-error">{{ $message }}</p>@enderror
            <p class="hint">{{ __('Mitglieder dieser Gruppen müssen den zweiten Faktor einrichten — unabhängig von ihren Rechten in der Anwendung. Wirkt ab der nächsten Anmeldung.') }}</p>
        </div>

        {{-- ===== 3. Rollen aus Gruppen / Roles from groups ===== --}}
        <div class="panel">
            <h2 style="margin-top:0">3 · {{ __('Rollen aus Gruppen des Verzeichnisses') }}</h2>
            <p class="hint" style="margin-top:0">{{ __('Gilt für Konten aus dem Verzeichnis: Gruppen als vollständiger DN oder nur als Gruppenname. Bei eigenen Konten vergeben Sie die Rechte unter „Benutzer“.') }}
                {{ __('Ein Gruppenname wird beim Speichern zum vollständigen DN, damit eine gleichnamige Gruppe in einer anderen OU nicht mitzählt. Gibt es den Namen mehrfach, ist der DN einzutragen.') }}</p>
            <label for="ad_group_admins">{{ __('Gruppe: Administratoren') }}</label>
            <input type="text" id="ad_group_admins" name="ad_group_admins" value="{{ old('ad_group_admins', $settings['ad_group_admins']) }}" placeholder="LinkHub-Admins" @error('ad_group_admins') aria-invalid="true" aria-describedby="ad_group_admins-error" @enderror>
            @error('ad_group_admins')<p class="field-error" id="ad_group_admins-error">{{ $message }}</p>@enderror
            <p class="hint">{{ __('Mitglieder erhalten Vollzugriff inklusive Benutzer, Einstellungen, Audit-Log und Backup. Leer = Wert aus der Konfiguration (AD_GROUP_ADMINS).') }}</p>

            <label for="ad_group_managers">{{ __('Gruppe: Manager') }}</label>
            <input type="text" id="ad_group_managers" name="ad_group_managers" value="{{ old('ad_group_managers', $settings['ad_group_managers']) }}" placeholder="LinkHub-Manager" @error('ad_group_managers') aria-invalid="true" aria-describedby="ad_group_managers-error" @enderror>
            @error('ad_group_managers')<p class="field-error" id="ad_group_managers-error">{{ $message }}</p>@enderror
            <p class="hint">{{ __('Mitglieder pflegen Applikationen, Bereiche, Abteilungen und Kategorien. Leer = Wert aus der Konfiguration (AD_GROUP_MANAGERS).') }}</p>

            <p class="hint">{{ __('Änderungen an den Gruppen wirken ab der nächsten Anmeldung der Benutzer. Verschachtelte Gruppen werden bei Active Directory aufgelöst.') }}</p>
        </div>

        {{-- ===== 4. Lizenz / Licence ===== --}}
        <div class="panel">
            <h2 style="margin-top:0">4 · {{ __('Lizenz') }}</h2>
            <div class="row">
                <div><label for="license_company">{{ __('Firma (Lizenznehmer)') }}</label><input type="text" id="license_company" name="license_company" value="{{ old('license_company', $settings['license_company']) }}" placeholder="Musterfirma GmbH"></div>
                <div><label for="license_key">{{ __('Lizenzschlüssel (20 Zeichen)') }}</label><input type="text" id="license_key" name="license_key" value="{{ old('license_key', $settings['license_key']) }}" maxlength="20" placeholder="2ABCD123426020299999" style="font-family:monospace"></div>
            </div>
            <p class="hint">{{ __('Schema: Stelle 1 = 1 Standard- / 2 Enterprise-Lizenz · Stellen 2–9 = Kundennummer (Buchstaben/Zahlen) · Stellen 10–15 = Support-Ende als JJMMTT (z. B. 260202) · Stellen 16–20 = Lizenzanzahl (99999 = unlimitiert). Nur schema-konforme Schlüssel werden gespeichert. Die Anzeige erfolgt im Footer unter „Hilfe“ – ein abgelaufener Support wird dort rot angezeigt, hat aber keine funktionalen Auswirkungen.') }}</p>
        </div>

        {{-- ===== 5. Backup ===== --}}
        <div class="panel">
            <h2 style="margin-top:0">5 · {{ __('Backup') }}</h2>
            <div class="row">
                <div style="max-width:260px">
                    <label for="backup_retention_days">{{ __('Aufbewahrung automatischer Backups (Tage)') }}</label>
                    <input type="number" id="backup_retention_days" name="backup_retention_days" value="{{ old('backup_retention_days', $settings['backup_retention_days'] ?? config('linkhub.backup_min_retention_days')) }}" min="1" max="3650" required>
                </div>
            </div>
            <p class="hint">{{ __('Nächtliche Sicherung um 01:30 Uhr in den Backup-Ordner (mit SHA-256-Prüfsummen). Mindestaufbewahrung: :n Tage — kürzere Werte werden automatisch angehoben. Manuelle Backups unter backups/manual werden nie automatisch gelöscht.', ['n' => config('linkhub.backup_min_retention_days')]) }}</p>
            <p class="hint">{{ __('Jede Sicherung enthält Datenbank, Logo und den App-Schlüssel. Ohne den Schlüssel wären Servicekonto-Passwort und Zwei-Faktor nach einer Wiederherstellung auf neuer Hardware nicht lesbar. Den Ordner backups dürfen deshalb nur Administratoren lesen.') }}</p>
        </div>

        @include('partials.legal-settings', ['number' => '6'])

        <button class="primary">{{ __('Einstellungen speichern') }}</button>
    </form>

    <div class="panel" style="margin-top:1.2rem">
        <h2 style="margin-top:0">{{ __('Verbindung zum Verzeichnis testen') }}</h2>
        <form method="post" action="{{ route('settings.testldap') }}">
            @csrf
            <button class="primary">{{ __('Verbindung testen') }}</button>
        </form>
        <p class="hint">{{ __('Prüft mit den gespeicherten Angaben aus Abschnitt 2: Server erreichbar, Verschlüsselung, Servicekonto, Suchbasis lesbar.') }}</p>
    </div>

    <div class="panel" style="margin-top:1.2rem">
        <h2 style="margin-top:0">{{ __('Backup jetzt erstellen') }}</h2>
        <form method="post" action="{{ route('backup.now') }}">
            @csrf
            <button class="primary">{{ __('Jetzt zusätzliches Backup erstellen') }}</button>
        </form>
        <p class="hint">{{ __('Schreibt unabhängig vom Zeitplan ein Backup mit Prüfsummendatei nach backups/manual — z. B. direkt vor einem Update oder vor der Übernahme in das Testsystem.') }}</p>
    </div>

    @if(config('test.enabled'))
    <div class="panel" style="border:2px solid var(--warn-ink)">
        <h2 style="margin-top:0">{{ __('Testsystem') }}</h2>
        <p class="hint">{{ __('Diese Instanz läuft im Testsystem-Modus (eigener Datenbestand, Port 7281). Hier lässt sich der letzte Produktionsstand einspielen — dabei werden alle Testdaten ersetzt. Quelle ist die neueste Sicherung aus dem Backup-Ordner der Produktion (inkl. manueller Backups); die Prüfsumme wird vor dem Einspielen verifiziert. Tipp: In der Produktion vorher „Jetzt zusätzliches Backup erstellen“ klicken, dann ist der Stand minutenaktuell.') }}</p>
        <p class="hint">{{ __('Danach gelten die Konten der Produktion: Sie werden abgemeldet und melden sich mit einem Konto der Produktion neu an. Den App-Schlüssel der Produktion übernimmt das Testsystem nicht; verschlüsselte Angaben der Produktion (Servicekonto-Passwort, Zwei-Faktor) sind hier nicht lesbar.') }}</p>
        @error('restore')<div class="flash err">{{ $message }}</div>@enderror
        <form method="post" action="{{ route('test-system.restore') }}"
              onsubmit="return confirm(@js(__('Letzten Produktions-Backup ins Testsystem einspielen? ALLE aktuellen Testdaten werden dabei ersetzt. Fortfahren?')))">
            @csrf <button class="primary danger">{{ __('Produktionsstand jetzt einspielen') }}</button>
        </form>
    </div>
    @endif
@endsection

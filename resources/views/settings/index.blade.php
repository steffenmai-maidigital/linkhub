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
                <div><label>{{ __('Firmenname (im Header, ersetzt „LinkHub“)') }}</label><input type="text" name="brand_company_name" value="{{ $settings['brand_company_name'] }}" placeholder="Musterfirma GmbH"></div>
            </div>
            <label>{{ __('Firmenlogo (PNG, JPG, SVG oder WebP, max. 2 MB)') }}</label>
            @if($settings['brand_logo_path'])
                <p><img src="{{ asset($settings['brand_logo_path']) }}" alt="{{ __('Aktuelles Logo') }}" class="logo-preview"></p>
                <label class="choice"><input type="checkbox" name="remove_logo" value="1"> {{ __('Logo entfernen') }}</label>
            @endif
            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg,.webp">
            <div class="row" style="margin-top:.6rem">
                <div style="max-width:200px"><label>{{ __('Header-Farbe') }}</label><input type="color" name="brand_header_color" value="{{ $settings['brand_header_color'] ?: '#14557b' }}"></div>
                <div style="max-width:200px"><label>{{ __('Akzentfarbe (Buttons, Links)') }}</label><input type="color" name="brand_accent_color" value="{{ $settings['brand_accent_color'] ?: '#14557b' }}"></div>
                <div style="max-width:200px"><label>{{ __('Footer-Farbe') }}</label><input type="color" name="brand_footer_color" value="{{ $settings['brand_footer_color'] ?: '#1d2733' }}"></div>
            </div>
            <label>{{ __('Footer-Text') }}</label>
            <input type="text" name="brand_footer_text" value="{{ $settings['brand_footer_text'] }}" placeholder="© Musterfirma GmbH · {{ __('Link- und Applikationsportal') }}">
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
                <div><label>{{ __('Server (Hostname oder IP, mehrere mit Komma)') }}</label><input type="text" name="ldap_host" value="{{ old('ldap_host', $directory['host']) }}" placeholder="dc01.firma.local" autocapitalize="none" spellcheck="false"></div>
                <div style="max-width:120px"><label>{{ __('Port') }}</label><input type="number" name="ldap_port" value="{{ old('ldap_port', $directory['port']) }}" min="1" max="65535"></div>
                <div style="max-width:240px">
                    <label>{{ __('Verschlüsselung') }}</label>
                    <select name="ldap_encryption">
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
            <label>{{ __('Suchbasis (Base DN)') }}</label>
            <input type="text" name="ldap_base_dn" value="{{ old('ldap_base_dn', $directory['base_dn']) }}" placeholder="DC=firma,DC=local" autocapitalize="none" spellcheck="false">
            <div class="row">
                <div><label>{{ __('Servicekonto (DN oder Anmeldename, nur lesend)') }}</label><input type="text" name="ldap_bind_dn" value="{{ old('ldap_bind_dn', $directory['bind_dn']) }}" placeholder="CN=svc-linkhub,OU=Service,DC=firma,DC=local" autocomplete="off"></div>
                <div><label>{{ __('Passwort des Servicekontos') }} <span class="hint">({{ __('leer lassen = unverändert') }})</span></label><input type="password" name="ldap_bind_password" value="" autocomplete="new-password"></div>
            </div>
            <details style="margin-top:.6rem">
                <summary style="cursor:pointer;font-size:.9rem">{{ __('Weitere Angaben (meist nicht nötig)') }}</summary>
                <div class="row">
                    <div><label>{{ __('Attribut mit dem Benutzernamen') }}</label><input type="text" name="ldap_user_attribute" value="{{ old('ldap_user_attribute', $directory['user_attribute']) }}" placeholder="sAMAccountName"><p class="hint">{{ __('Active Directory: sAMAccountName · OpenLDAP/Univention: uid') }}</p></div>
                    <div><label>{{ __('Benutzerfilter (LDAP-Filter)') }}</label><input type="text" name="ldap_user_filter" value="{{ old('ldap_user_filter', $directory['user_filter']) }}" placeholder="(&(objectClass=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))"><p class="hint">{{ __('Schränkt ein, wer sich anmelden darf, z. B. nur aktive Konten einer OU.') }}</p></div>
                </div>
                <div class="row">
                    <div><label>{{ __('Ohne Servicekonto: Anmeldename nach Vorlage') }}</label><input type="text" name="ldap_bind_template" value="{{ old('ldap_bind_template', $directory['bind_template']) }}" placeholder="%s@firma.local"><p class="hint">{{ __('%s wird durch den eingegebenen Benutzernamen ersetzt, z. B. %s@firma.local oder uid=%s,ou=people,dc=firma,dc=local.') }}</p></div>
                    <div style="max-width:160px"><label>{{ __('Zeitlimit (Sekunden)') }}</label><input type="number" name="ldap_timeout" value="{{ old('ldap_timeout', $directory['timeout']) }}" min="1" max="60"></div>
                </div>
            </details>
            <p class="hint">{{ __('Nach dem Speichern unten „Verbindung testen“ anklicken. Ein eigenes Admin-Konto bleibt als Notzugang immer möglich (docker exec linkhub php artisan linkhub:admin-password).') }}</p>
            <h3 style="margin:1rem 0 .2rem">🔐 {{ __('Zwei-Faktor-Anmeldung') }}</h3>
            <p class="hint" style="margin-top:0">{{ __('Zusätzlicher Code aus einer Authenticator-App — je Gruppe: Pflicht (muss eingerichtet werden), wählbar (jeder kann) oder aus. Gehört jemand zu mehreren Gruppen, gilt die strengste Stufe. Ausnahmen je Benutzer unter „Benutzer“.') }}</p>
            <div class="row">
                @foreach(\App\Support\Mfa::groups() as $mfaKey => [$mfaLabel])
                    <div style="max-width:260px">
                        <label>{{ $mfaLabel }}</label>
                        <select name="mfa_{{ $mfaKey }}">
                            @foreach(\App\Support\Mfa::LEVELS as $level => $levelLabel)
                                <option value="{{ $level }}" @selected(old('mfa_' . $mfaKey, \App\Support\Mfa::level($mfaKey)) === $level)>{{ __($levelLabel) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            <label>{{ __('Verzeichnisgruppen mit Pflicht (eine je Zeile, DN oder Name)') }}</label>
            <textarea name="mfa_required_groups" rows="2" placeholder="Geschaeftsfuehrung&#10;CN=IT-Admins,OU=Gruppen,DC=firma,DC=local">{{ old('mfa_required_groups', \App\Models\Setting::get('mfa_required_groups')) }}</textarea>
            <p class="hint">{{ __('Mitglieder dieser Gruppen müssen den zweiten Faktor einrichten — unabhängig von ihren Rechten in der Anwendung. Wirkt ab der nächsten Anmeldung.') }}</p>
        </div>

        {{-- ===== 3. Rollen aus Gruppen / Roles from groups ===== --}}
        <div class="panel">
            <h2 style="margin-top:0">3 · {{ __('Rollen aus Gruppen des Verzeichnisses') }}</h2>
            <p class="hint" style="margin-top:0">{{ __('Gilt für Konten aus dem Verzeichnis: Gruppen als vollständiger DN oder nur als Gruppenname. Bei eigenen Konten vergeben Sie die Rechte unter „Benutzer“.') }}</p>
            <label>{{ __('Gruppe: Administratoren') }}</label>
            <input type="text" name="ad_group_admins" value="{{ $settings['ad_group_admins'] }}" placeholder="LinkHub-Admins">
            <p class="hint">{{ __('Mitglieder erhalten Vollzugriff inklusive Benutzer, Einstellungen, Audit-Log und Backup. Leer = Wert aus der Konfiguration (AD_GROUP_ADMINS).') }}</p>

            <label>{{ __('Gruppe: Manager') }}</label>
            <input type="text" name="ad_group_managers" value="{{ $settings['ad_group_managers'] }}" placeholder="LinkHub-Manager">
            <p class="hint">{{ __('Mitglieder pflegen Applikationen, Bereiche, Abteilungen und Kategorien. Leer = Wert aus der Konfiguration (AD_GROUP_MANAGERS).') }}</p>

            <p class="hint">{{ __('Änderungen an den Gruppen wirken ab der nächsten Anmeldung der Benutzer. Verschachtelte Gruppen werden bei Active Directory aufgelöst.') }}</p>
        </div>

        {{-- ===== 4. Lizenz / Licence ===== --}}
        <div class="panel">
            <h2 style="margin-top:0">4 · {{ __('Lizenz') }}</h2>
            <div class="row">
                <div><label>{{ __('Firma (Lizenznehmer)') }}</label><input type="text" name="license_company" value="{{ $settings['license_company'] }}" placeholder="Musterfirma GmbH"></div>
                <div><label>{{ __('Lizenzschlüssel (20 Zeichen)') }}</label><input type="text" name="license_key" value="{{ $settings['license_key'] }}" maxlength="20" placeholder="2ABCD123426020299999" style="font-family:monospace"></div>
            </div>
            <p class="hint">{{ __('Schema: Stelle 1 = 1 Standard- / 2 Enterprise-Lizenz · Stellen 2–9 = Kundennummer (Buchstaben/Zahlen) · Stellen 10–15 = Support-Ende als JJMMTT (z. B. 260202) · Stellen 16–20 = Lizenzanzahl (99999 = unlimitiert). Nur schema-konforme Schlüssel werden gespeichert. Die Anzeige erfolgt im Footer unter „Hilfe“ – ein abgelaufener Support wird dort rot angezeigt, hat aber keine funktionalen Auswirkungen.') }}</p>
        </div>

        {{-- ===== 5. Backup ===== --}}
        <div class="panel">
            <h2 style="margin-top:0">5 · {{ __('Backup') }}</h2>
            <div class="row">
                <div style="max-width:260px">
                    <label>{{ __('Aufbewahrung automatischer Backups (Tage)') }}</label>
                    <input type="number" name="backup_retention_days" value="{{ $settings['backup_retention_days'] ?? config('linkhub.backup_min_retention_days') }}" min="1" max="3650" required>
                </div>
            </div>
            <p class="hint">{{ __('Nächtliche Sicherung um 01:30 Uhr in den Backup-Ordner (mit SHA-256-Prüfsummen). Mindestaufbewahrung: :n Tage — kürzere Werte werden automatisch angehoben. Manuelle Backups unter backups/manual werden nie automatisch gelöscht.', ['n' => config('linkhub.backup_min_retention_days')]) }}</p>
        </div>

        {{-- ===== 6. Fuchs-Helfer / Fox helper ===== --}}
        <div class="panel">
            <h2 style="margin-top:0">6 · 🦊 {{ __('Fuchs-Helfer') }}</h2>
            <p>{{ __('Ein freundlicher Fuchs unten rechts begrüßt angemeldete Benutzer, gibt Tipps passend zur Rolle und zur Seite und wirbt auf der Anmeldeseite für freie Software. Auf dem öffentlichen Portal erscheint er nicht.') }}</p>
            <label class="choice"><input type="checkbox" name="fox_enabled" value="1" @checked(old('fox_enabled', ($settings['fox_enabled'] ?? '1') === '1'))> <b>{{ __('Fuchs für alle anzeigen') }}</b></label>
            <p class="hint">{{ __('Ausgeschaltet ist er für niemanden sichtbar. Eingeschaltet kann ihn jede Person unter „Hilfe“ für sich selbst ausblenden.') }}</p>
        </div>

        @include('partials.legal-settings', ['number' => '7'])

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
        @error('restore')<div class="flash err">{{ $message }}</div>@enderror
        <form method="post" action="{{ route('test-system.restore') }}"
              onsubmit="return confirm('{{ __('Letzten Produktions-Backup ins Testsystem einspielen? ALLE aktuellen Testdaten werden dabei ersetzt. Fortfahren?') }}')">
            @csrf <button class="primary danger">{{ __('Produktionsstand jetzt einspielen') }}</button>
        </form>
    </div>
    @endif
@endsection

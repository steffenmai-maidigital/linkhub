@extends('layouts.app')
@section('title', __('Hilfe'))
@section('content')
    <h1>❓ {{ __('Hilfe') }}</h1>

    {{-- 1 · Kurzanleitung (Standard 03) / Quick guide --}}
    <div class="panel">
        <h2 style="margin-top:0">📘 {{ __('Kurzanleitung') }}</h2>
        <ul class="help-list">
            <li>{{ __('Portal: Leiste oben filtert nach Bereich und Abteilung, die Suche rechts findet nach Name und Beschreibung. „Öffnen“ startet die Anwendung in einem neuen Tab.') }}</li>
            @if($account->canEdit())
                <li>{{ __('Unter „Applikationen“ legen Sie Einträge an und bearbeiten sie — ohne Zuordnung erscheinen sie nur unter „Alle“.') }}</li>
                <li>{{ __('Unter „Struktur“ pflegen Sie Bereiche, Abteilungen und Kategorien — mit optionalen englischen Namen.') }}</li>
            @endif
            @if($account->is_admin)
                <li>{{ __('Administratoren verwalten Benutzer, Einstellungen (Firma, Anmeldung, Zwei-Faktor, Rollen, Lizenz, Backup) und das Audit-Log.') }}</li>
            @endif
            <li>{{ __('Unter „Mein Konto“ (Name oben rechts) ändern Sie Passwort, Zwei-Faktor-Anmeldung und den Fuchs-Helfer.') }}</li>
            <li>{{ __('Sprache (Flagge) und Hell/Dunkel (◐) stellen Sie oben rechts um.') }}</li>
        </ul>
        <p class="hint" style="margin-bottom:0">{{ __('Ausführlich im') }} <a href="{{ route('help.doc', 'user') }}">{{ __('Benutzerhandbuch') }}</a>.</p>
    </div>

    {{-- 2 · System --}}
    <div class="panel">
        <h2 style="margin-top:0">🖥️ {{ __('System') }}</h2>
        <table>
            <tr><th style="width:200px">{{ __('Produkt') }}</th><td>LinkHub · {{ __('Link- und Applikationsportal') }} · {{ \App\Support\ProductInfo::edition() }}</td></tr>
            <tr><th>{{ __('Version') }}</th><td>{{ config('version.number') }}</td></tr>
            <tr><th>{{ __('Build') }}</th><td><code>{{ config('version.build') }}</code></td></tr>
            @if(\App\Models\Setting::get('app_updated_at'))
                <tr><th>{{ __('Zuletzt aktualisiert') }}</th><td>{{ \Illuminate\Support\Carbon::parse(\App\Models\Setting::get('app_updated_at'))->format('d.m.Y H:i') }}</td></tr>
            @endif
            <tr><th>PHP / Laravel</th><td>{{ PHP_VERSION }} / {{ app()->version() }}</td></tr>
            <tr><th>{{ __('Anmeldung') }}</th><td>{{ __('eigene Konten') }}@if($directory) + {{ __('Verzeichnis (Active Directory / LDAP)') }}@endif</td></tr>
            <tr><th>{{ __('Ihr Konto') }}</th><td>{{ $account->origin() }} · {{ __('Zwei-Faktor') }}: {{ $account->hasTwoFactor() ? __('eingeschaltet') : __('nicht eingerichtet') }} · <a href="{{ route('account') }}">{{ __('Mein Konto') }}</a></td></tr>
            <tr><th>{{ __('Systemmodus') }}</th><td>
                @if(config('test.enabled'))<span style="color:#c0392b;font-weight:700">TESTSYSTEM</span>
                @elseif(config('demo.enabled')){{ __('Demo-Modus aktiv') }}
                @else{{ __('Produktivsystem') }}@endif
            </td></tr>
        </table>
    </div>

    {{-- 3 · Lizenz / Licence --}}
    <div class="panel">
        <h2 style="margin-top:0">📜 {{ __('Lizenz') }}</h2>
        @if($license)
            <table>
                <tr><th style="width:200px">{{ __('Lizenzname') }}</th><td>{{ __($license['name']) }}</td></tr>
                @if($licenseCompany)<tr><th>{{ __('Lizenziert für') }}</th><td>{{ $licenseCompany }}</td></tr>@endif
                <tr><th>{{ __('Kundennummer') }}</th><td>{{ $license['customer_no'] }}</td></tr>
                <tr><th>{{ __('Lizenzierte Benutzer') }}</th><td>{{ __($license['count_display']) }}
                    <span class="hint">— {{ __('genutzt:') }} {{ $usedCount }}</span>
                    @if($license['count'] < 99999 && $usedCount > $license['count'])<span style="color:#c0392b;font-weight:600">({{ __('überschritten') }})</span>@endif</td></tr>
                <tr><th>{{ __('Lizenzcode') }}</th><td><code>{{ $license['key'] }}</code></td></tr>
                <tr><th>{{ __('Support bis') }}</th><td>
                    @if($license['expired'])<span style="color:#c0392b;font-weight:700">{{ $license['support_end']->format('d.m.Y') }} – {{ __('Support abgelaufen') }}</span>
                    @else{{ $license['support_end']->format('d.m.Y') }}@endif
                </td></tr>
            </table>
            @if($license['expired'])
                <p class="hint">{{ __('Der Ablauf des Supports hat keine funktionalen Auswirkungen – die Anwendung läuft uneingeschränkt weiter.') }}</p>
            @endif
        @elseif($rawKey)
            <p style="color:#c0392b">{{ __('Der hinterlegte Lizenzschlüssel entspricht nicht dem gültigen Schema.') }}</p>
        @else
            <p>{{ __('Community Edition — freie Open-Source-Fassung (AGPL-3.0), vollständig und ohne Einschränkungen nutzbar.') }}</p>
            <p class="hint" style="margin-bottom:0">{{ __('Es ist keine Lizenz gepflegt. Administratoren können Firma und Lizenzschlüssel unter Einstellungen → Lizenz hinterlegen.') }}</p>
        @endif
    </div>

    {{-- 4 · Handbücher / Manuals --}}
    <div class="panel">
        <h2 style="margin-top:0">📚 {{ __('Handbücher') }}</h2>
        <table>
            @foreach($docs as $key => [$label, $file, $adminOnly, $summary])
                @continue($adminOnly && ! $isAdmin)
                @continue($key === 'changelog')
                <tr><td><a href="{{ route('help.doc', $key) }}">{{ __($label) }}</a></td><td class="hint">{{ __($summary) }}</td></tr>
            @endforeach
        </table>
        <p class="hint" style="margin-bottom:0">{{ __('Die Handbücher liegen auf Deutsch vor.') }}</p>
    </div>

    {{-- 5 · Änderungshistorie / Change history --}}
    <div class="panel">
        <h2 style="margin-top:0">📋 {{ __('Änderungshistorie') }}</h2>
        <p style="margin:0">{{ __('Was in welcher Version neu ist, steht im') }} <a href="{{ route('help.doc', 'changelog') }}">{{ __('Changelog') }}</a> — {{ __('aktuell installiert') }}: {{ config('version.number') }}.</p>
    </div>

    {{-- 6 · Letzte Fehler (Administratoren) / Recent errors (administrators) --}}
    @if($recentErrors !== null)
        <div class="panel">
            <h2 style="margin-top:0">🐞 {{ __('Letzte Fehler (nur für Administratoren)') }}</h2>
            @if($recentErrors['count'] === 0)
                <p class="hint" style="margin:0">{{ __('Keine Fehler in den letzten 30 Tagen – alles in Ordnung.') }}</p>
            @else
                <p class="hint" style="margin-top:0">{{ __('Die letzten Fehlereinträge aus dem Anwendungslog (gekürzt). Bei Supportanfragen bitte diese Zeilen mitschicken — ohne echte Personen- oder Kundendaten.') }}
                    {{ __(':n Fehler in den letzten 30 Tagen.', ['n' => $recentErrors['count']]) }}</p>
                @foreach($recentErrors['lines'] as $line)
                    <pre class="log-line">{{ $line }}</pre>
                @endforeach
                <form method="post" action="{{ route('help.clear-log') }}" style="margin-top:.6rem" onsubmit="return confirm(@js(__('Das gesamte Anwendungsprotokoll leeren?')))">
                    @csrf @method('DELETE')<button class="primary small">{{ __('Protokoll leeren') }}</button>
                </form>
            @endif
        </div>
    @endif

    {{-- 7 · Support --}}
    <div class="panel">
        <h2 style="margin-top:0">💬 {{ __('Support') }}</h2>
        <p>{{ __('Bei Fragen oder Problemen wenden Sie sich bitte an Ihre IT-Abteilung bzw. den Systemadministrator. Halten Sie dafür die Kundennummer aus der Lizenz und die Build-Nummer bereit.') }}</p>
        <table>
            <tr><th style="width:200px">🐙 GitHub</th><td>{{ __('Quellcode, Fehler melden und Ideen einbringen') }}:
                <a href="{{ config('product.github') }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', config('product.github')) }}</a>
                · <a href="{{ rtrim(config('product.github'), '/') }}/issues" target="_blank" rel="noopener">{{ __('Fehler melden') }}</a></td></tr>
            <tr><th>🌍 {{ __('Projektseite') }}</th><td>{{ __('Neuigkeiten, Anleitungen und unterstützte Version') }}:
                <a href="{{ \App\Support\ProductInfo::projectUrl() }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', \App\Support\ProductInfo::projectUrl()) }}</a></td></tr>
        </table>
        <p class="hint" style="margin-bottom:0">{{ __('Bitte keine echten Personen- oder Kundendaten in öffentliche Fehlermeldungen schreiben.') }}</p>
    </div>
@endsection

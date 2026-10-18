@extends('layouts.app')
@section('title', __('Anmelden'))
@section('content')
    <div class="panel login-box" style="max-width:420px;margin:3rem auto;">
        <div style="text-align:center;margin-bottom:.6rem">
            <img src="{{ \App\Models\Setting::get('brand_logo_path') ? asset(\App\Models\Setting::get('brand_logo_path')) : asset('img/linkhub.svg') }}"
                 alt="" width="56" height="56" style="border-radius:14px;box-shadow:0 8px 22px color-mix(in srgb, var(--accent) 35%, transparent);object-fit:contain">
            <div style="font-weight:700;font-size:1.25rem;letter-spacing:.3px;margin-top:.35rem">{{ \App\Models\Setting::get('brand_company_name') ?: 'LinkHub' }}</div>
            <div class="hint" style="margin-top:.15rem">LinkHub · {{ __('Version') }} {{ config('version.number') }}@if(config('test.enabled')) · <span style="color:#c0392b;font-weight:700">TESTSYSTEM</span>@endif</div>
        </div>
        <h1>{{ __('Anmelden') }}</h1>
        <p class="hint">{{ __('Anmeldung nur für die Pflege — das Portal selbst ist ohne Login nutzbar.') }}
            @if(config('demo.enabled'))
                {{ __('Melden Sie sich mit einem der Demo-Konten an.') }}
            @elseif($directory)
                {{ __('Melden Sie sich mit Ihrem Firmenkonto (Windows-Anmeldung) oder mit Ihrem LinkHub-Konto an.') }}
            @else
                {{ __('Melden Sie sich mit Ihrem LinkHub-Konto an.') }}
            @endif
        </p>
        @if(config('demo.enabled'))
            <div class="flash ok" style="margin-top:.6rem">
                <strong>{{ __('Demo-Modus aktiv') }}</strong> – {{ __('kein Verzeichnis nötig.') }}<br><br>
                <code>admin</code> / <code>admin</code> – {{ __('Administration & Einstellungen') }}<br>
                <code>manager</code> / <code>manager</code> – {{ __('Pflege der Inhalte') }}
            </div>
        @endif
        <form method="post" action="{{ route('login') }}">
            @csrf
            <label for="username">{{ __('Benutzername') }}</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" autofocus required autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false">
            <label for="password">{{ __('Passwort') }}</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button class="primary" style="width:100%">{{ __('Anmelden') }}</button>
        </form>
        <p class="hint" style="text-align:center;margin-bottom:0;margin-top:1rem"><a href="{{ route('home') }}">← {{ __('Zurück zum Portal') }}</a></p>
        @include('partials.legal-links')
    </div>
@endsection

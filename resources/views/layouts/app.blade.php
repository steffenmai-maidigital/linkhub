<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'LinkHub') · LinkHub</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/linkhub.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ config('version.build') }}">
    {{-- Einheitliche Symbole (Twemoji) statt Geräte-Emojis, Standard 03 / Twemoji symbols instead of device emojis --}}
    <meta name="symbol-base" content="{{ rtrim(asset(''), '/') }}">
    <script src="{{ asset('js/symbols.js') }}?v={{ config('version.build') }}" defer></script>
    @php
        $brand = [
            'name'   => \App\Models\Setting::get('brand_company_name'),
            'logo'   => \App\Models\Setting::get('brand_logo_path'),
            'header' => \App\Models\Setting::get('brand_header_color'),
            'accent' => \App\Models\Setting::get('brand_accent_color'),
            'footerBg' => \App\Models\Setting::get('brand_footer_color'),
            'footerText' => \App\Models\Setting::get('brand_footer_text'),
        ];
    @endphp
    @if($brand['header'] || $brand['accent'] || $brand['footerBg'])
        <style>
            :root, html[data-theme="dark"] {
                @if($brand['accent']) --accent: {{ $brand['accent'] }}; @endif
                @if($brand['header']) --header-bg: {{ $brand['header'] }}; @endif
                @if($brand['footerBg']) --footer-bg: {{ $brand['footerBg'] }}; @endif
            }
        </style>
    @endif
    <script>
        // Hell/Dunkel (Standard 03): gespeicherte Wahl, sonst Systemeinstellung
        // Light/dark (Standard 03): saved choice, otherwise the system setting
        (function () {
            var t = null; try { t = localStorage.getItem('theme'); } catch (e) {}
            document.documentElement.dataset.theme = t || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        })();
    </script>
</head>
<body>
@if(config('test.enabled'))
    <div class="test-banner">TESTSYSTEM</div>
@endif
@php($u = auth()->user())
<header class="top">
    <a class="brand" href="{{ route('home') }}">
        <img src="{{ $brand['logo'] ? asset($brand['logo']) : asset('img/linkhub.svg') }}" alt="">
        {{ $brand['name'] ?: 'LinkHub' }}
    </a>
    <nav aria-label="{{ __('Hauptmenü') }}">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">{{ __('Portal') }}</a>
        @if($u?->canEdit())
            <a href="{{ route('applications.index') }}" class="{{ request()->routeIs('applications.*') ? 'active' : '' }}">{{ __('Applikationen') }}</a>
            <a href="{{ route('structure.index') }}" class="{{ request()->routeIs('structure.*') ? 'active' : '' }}">{{ __('Struktur') }}</a>
        @endif
        @if($u?->is_admin)
            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">{{ __('Benutzer') }}</a>
            <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">{{ __('Einstellungen') }}</a>
            <a href="{{ route('audit.index') }}" class="{{ request()->routeIs('audit.*') ? 'active' : '' }}">{{ __('Audit-Log') }}</a>
        @endif
    </nav>
    <span class="who">
        <details class="lang-switch">
            <summary>
                <img src="{{ asset(app()->getLocale() === 'de' ? 'img/flag-de.svg' : 'img/flag-gb.svg') }}" alt="">
                {{ app()->getLocale() === 'de' ? 'Deutsch' : 'English' }} ▾
            </summary>
            <div class="lang-menu">
                <a href="{{ route('locale.switch', 'de') }}"><img src="{{ asset('img/flag-de.svg') }}" alt=""> Deutsch</a>
                <a href="{{ route('locale.switch', 'en') }}"><img src="{{ asset('img/flag-gb.svg') }}" alt=""> English</a>
            </div>
        </details>
        <button type="button" class="theme" id="theme-toggle" title="{{ __('Hell/Dunkel umschalten') }}" aria-label="{{ __('Hell/Dunkel umschalten') }}">◐</button>
        @if($u)
            <a class="name" href="{{ route('account') }}" title="{{ __('Mein Konto') }}">{{ $u->name }}</a>
            <form method="post" action="{{ route('logout') }}" class="inline-form">@csrf<button>{{ __('Abmelden') }}</button></form>
        @else
            <a class="login" href="{{ route('login') }}">{{ __('Anmelden') }}</a>
        @endif
    </span>
</header>
<main>
    @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
    @if($errors->any())
        <div class="flash err">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif
    @yield('content')
</main>
@include('partials.footer', ['signedIn' => (bool) $u])
@if(\App\Support\Fox::visible())
    @include('partials.fox', ['foxPrefix' => 'lh-fox'])
@endif
<script>
    document.getElementById('theme-toggle').addEventListener('click', function () {
        const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = next;
        try { localStorage.setItem('theme', next); } catch (e) {}
    });
    // Aufklappmenüs (Sprache) schließen, wenn man daneben tippt
    // Close drop-down menus (language) when tapping outside them
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.lang-switch[open]').forEach(function (d) { if (!d.contains(e.target)) d.open = false; });
    });
</script>
</body>
</html>

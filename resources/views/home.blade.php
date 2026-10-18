@extends('layouts.app')
@section('title', __('Portal'))
@section('content')

<div class="filter-bar" role="navigation" aria-label="{{ __('Bereiche') }}">
    <a href="{{ route('home') }}" class="filter-pill {{ $activeArea ? '' : 'active' }}">{{ __('Alle') }}</a>
    @foreach($areas as $area)
        <a href="{{ route('home', ['area' => $area->slug]) }}"
           class="filter-pill {{ $activeArea?->id === $area->id ? 'active' : '' }}">{{ $area->displayName() }}</a>
    @endforeach
</div>

@if($activeArea && $activeArea->departments->isNotEmpty())
    <div class="filter-bar sub" role="navigation" aria-label="{{ __('Abteilungen') }}">
        <span class="lead">{{ __('Abteilung') }}</span>
        <a href="{{ route('home', ['area' => $activeArea->slug]) }}"
           class="filter-pill {{ $activeDept ? '' : 'active' }}">{{ __('Alle') }}</a>
        @foreach($activeArea->departments as $dept)
            <a href="{{ route('home', ['area' => $activeArea->slug, 'department' => $dept->slug]) }}"
               class="filter-pill {{ $activeDept?->id === $dept->id ? 'active' : '' }}">{{ $dept->displayName() }}</a>
        @endforeach
    </div>
@endif

<div class="portal-meta">
    <span class="hint">
        <strong style="color:var(--ink)">{{ $total }}</strong>
        {{ $total === 1 ? __('Applikation') : __('Applikationen') }}
        @if($activeArea) · {{ $activeArea->displayName() }}@if($activeDept) / {{ $activeDept->displayName() }}@endif @endif
    </span>
    <span class="portal-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" id="app-search" placeholder="{{ __('Applikation suchen …') }}" autocomplete="off" aria-label="{{ __('Applikation suchen') }}">
    </span>
</div>

@forelse($groups as $group)
    <div class="panel" data-group>
        <h2 style="margin-top:0">{{ $group['category']?->displayName() ?? __('Weitere') }}</h2>
        <div class="app-grid">
            @foreach($group['apps'] as $app)
                <div class="app-tile" style="--hue: {{ $app->hue() }}"
                     data-search="{{ mb_strtolower($app->name.' '.$app->name_en.' '.$app->description.' '.$app->description_en.' '.($app->category?->name ?? '').' '.($app->category?->name_en ?? '')) }}">
                    <div class="head">
                        <span class="mono" aria-hidden="true">{{ $app->monogram() }}</span>
                        <span style="min-width:0">
                            <span class="title" style="display:block">{{ $app->displayName() }}</span>
                            @if($app->category)<span class="badge">{{ $app->category->displayName() }}</span>@endif
                        </span>
                    </div>
                    @if($app->displayDescription())<p class="desc">{{ $app->displayDescription() }}</p>@endif
                    <div class="actions">
                        <a class="btn" href="{{ $app->url }}" target="_blank" rel="noopener">{{ __('Öffnen') }}</a>
                        @if($app->manual_url)
                            <a href="{{ $app->manual_url }}" target="_blank" rel="noopener" title="{{ __('Handbuch / Anleitung') }}">{{ __('Manual') }}</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="panel">
        <h2 style="margin-top:0">{{ __('Keine Applikationen vorhanden') }}</h2>
        <p class="hint" style="margin:0">
            @auth
                {{ __('Lege die erste Applikation unter „Applikationen“ an.') }}
            @else
                {{ __('Für diese Auswahl sind keine Einträge hinterlegt.') }}
            @endauth
        </p>
    </div>
@endforelse

<div class="panel" id="no-results" style="display:none">
    <h2 style="margin-top:0">{{ __('Keine Treffer') }}</h2>
    <p class="hint" style="margin:0">{{ __('Die Suche ergab keine passende Applikation.') }}</p>
</div>

<script>
(function () {
    const input = document.getElementById('app-search');
    if (!input) return;
    const tiles = Array.from(document.querySelectorAll('[data-search]'));
    const groups = Array.from(document.querySelectorAll('[data-group]'));
    const empty = document.getElementById('no-results');

    input.addEventListener('input', function () {
        const q = input.value.trim().toLowerCase();
        let hits = 0;
        tiles.forEach(t => {
            const show = q === '' || t.dataset.search.includes(q);
            t.style.display = show ? '' : 'none';
            if (show) hits++;
        });
        groups.forEach(g => {
            const visible = g.querySelectorAll('[data-search]:not([style*="display: none"])').length;
            g.style.display = visible > 0 ? '' : 'none';
        });
        if (empty) empty.style.display = (hits === 0 && q !== '') ? '' : 'none';
    });
})();
</script>
@endsection

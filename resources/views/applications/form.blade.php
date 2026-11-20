@extends('layouts.app')
@section('title', $item->exists ? __('Applikation bearbeiten') : __('Applikation anlegen'))
@section('content')
<h1>{{ $item->exists ? __('Applikation bearbeiten') : __('Applikation anlegen') }}</h1>
<form method="post" action="{{ $item->exists ? route('applications.update', $item) : route('applications.store') }}">
    @csrf @if($item->exists) @method('PUT') @endif

    <div class="panel">
        <h2 style="margin-top:0">{{ __('Grunddaten') }}</h2>
        <div class="row">
            <div><label for="name">{{ __('Name') }} *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $item->name) }}" required maxlength="120"></div>
            <div><label for="name_en">{{ __('Name (EN, optional)') }}</label>
                <input type="text" id="name_en" name="name_en" value="{{ old('name_en', $item->name_en) }}" maxlength="120"></div>
        </div>
        <div class="row">
            <div><label for="category_id">{{ __('Kategorie') }}</label>
                <select id="category_id" name="category_id">
                    <option value="">{{ __('— keine (Abschnitt „Weitere“) —') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $item->category_id) == $cat->id)>{{ $cat->displayName() }}</option>
                    @endforeach
                </select></div>
            <div style="max-width:160px"><label for="sort_order">{{ __('Reihenfolge') }}</label>
                <input type="number" id="sort_order" name="sort_order" min="0" max="65000" value="{{ old('sort_order', $item->sort_order ?? 0) }}"></div>
        </div>
        <label for="description">{{ __('Beschreibung') }} <span class="hint">— {{ __('kurzer Satz auf der Kachel') }}</span></label>
        <textarea id="description" name="description" rows="2" maxlength="1000">{{ old('description', $item->description) }}</textarea>
        <label for="description_en">{{ __('Beschreibung (EN, optional)') }}</label>
        <textarea id="description_en" name="description_en" rows="2" maxlength="1000">{{ old('description_en', $item->description_en) }}</textarea>
        <label class="choice" style="margin-top:.7rem">
            <input type="checkbox" name="active" value="1" @checked(old('active', $item->active ?? true))> {{ __('Aktiv (im Portal sichtbar)') }}</label>
    </div>

    <div class="panel">
        <h2 style="margin-top:0">{{ __('Links') }}</h2>
        <label for="url">{{ __('Link zur Applikation') }} *</label>
        <input type="text" id="url" name="url" value="{{ old('url', $item->url) }}" required maxlength="500" placeholder="https://…" inputmode="url" autocapitalize="none" spellcheck="false">
        <label for="manual_url">{{ __('Link zur Anleitung') }} <span class="hint">— {{ __('optional, erscheint als „Anleitung“ auf der Kachel') }}</span></label>
        <input type="text" id="manual_url" name="manual_url" value="{{ old('manual_url', $item->manual_url) }}" maxlength="500" placeholder="https://…" inputmode="url" autocapitalize="none" spellcheck="false">
    </div>

    <div class="panel">
        <h2 style="margin-top:0">{{ __('Sichtbarkeit') }}</h2>
        <p class="hint" style="margin-top:0">{{ __('Ohne Auswahl erscheint die Applikation nur im Filter „Alle“.') }}</p>
        <label id="areas-label">{{ __('Bereiche') }} <span class="hint">— {{ __('gilt für den ganzen Bereich') }}</span></label>
        <div class="choice-list" role="group" aria-labelledby="areas-label">
            @foreach($areas as $area)
                <label class="choice">
                    <input type="checkbox" name="areas[]" value="{{ $area->id }}"
                           @checked(collect(old('areas', $item->areas->pluck('id')))->contains($area->id))>
                    {{ $area->displayName() }}</label>
            @endforeach
        </div>
        <label id="departments-label">{{ __('Abteilungen') }} <span class="hint">— {{ __('nur dort (und im Bereichs-Filter)') }}</span></label>
        @foreach($areas as $area)
            @if($area->departments->isNotEmpty())
                <div class="cat-head">{{ $area->displayName() }}</div>
                <div class="choice-list" role="group" aria-label="{{ __('Abteilungen') }}: {{ $area->displayName() }}">
                    @foreach($area->departments as $dept)
                        <label class="choice">
                            <input type="checkbox" name="departments[]" value="{{ $dept->id }}"
                                   @checked(collect(old('departments', $item->departments->pluck('id')))->contains($dept->id))>
                            {{ $dept->displayName() }}</label>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>

    <button class="primary">{{ __('Speichern') }}</button>
    <a href="{{ route('applications.index') }}" style="margin-left:.8rem">{{ __('Abbrechen') }}</a>
</form>

@if($item->exists)
    <form method="post" action="{{ route('applications.destroy', $item) }}" style="margin-top:1rem"
          onsubmit="return confirm(@js(__('Applikation „:name“ wirklich löschen?', ['name' => $item->name])))">
        @csrf @method('DELETE')
        <button class="primary small danger">{{ __('Applikation löschen') }}</button>
    </form>
@endif
@endsection

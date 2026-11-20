@extends('layouts.app')
@section('title', __('Struktur'))
@section('content')
<h1>{{ __('Struktur') }}</h1>

{{-- ================= Bereiche / Areas ================= --}}
<div class="panel">
    <h2 style="margin-top:0">{{ __('Bereiche') }} <span class="hint">({{ __('oberste Filterebene im Portal — die Reihenfolge bestimmt die Sortierung') }})</span></h2>
    <table class="manage">
        <tr><th style="width:90px">{{ __('Reihenfolge') }}</th><th>{{ __('Name') }}</th><th style="width:70px">{{ __('Aktiv') }}</th><th class="hint">{{ __('Filter-Link') }}</th><th></th></tr>
        @forelse($areas as $area)
            <tr>
                <form method="post" action="{{ route('structure.update', ['areas', $area->id]) }}">
                    @csrf @method('PUT')
                    <td data-label="{{ __('Reihenfolge') }}"><input type="number" name="sort_order" value="{{ $area->sort_order }}" min="0" max="65000" aria-label="{{ __('Reihenfolge') }}"></td>
                    <td data-label="{{ __('Name') }}">
                        <input type="text" name="name" value="{{ $area->name }}" required maxlength="100" aria-label="{{ __('Name') }}">
                        <input type="text" name="name_en" value="{{ $area->name_en }}" placeholder="{{ __('Name (EN, optional)') }}" maxlength="100" aria-label="{{ __('Name (EN, optional)') }}" style="margin-top:.3rem">
                    </td>
                    <td data-label="{{ __('Aktiv') }}"><label class="choice"><input type="checkbox" name="active" value="1" @checked($area->active) aria-label="{{ __('Aktiv') }}"></label></td>
                    <td class="hint" data-label="{{ __('Filter-Link') }}">/{{ $area->slug }}</td>
                    <td class="actions" style="white-space:nowrap">
                        <button class="primary small">{{ __('Speichern') }}</button>
                </form>
                <form method="post" action="{{ route('structure.destroy', ['areas', $area->id]) }}" class="inline-form"
                      onsubmit="return confirm(@js(__('Bereich „:name“ samt seiner Abteilungen löschen? Applikationen bleiben erhalten.', ['name' => $area->name])))">
                    @csrf @method('DELETE')
                    <button class="primary small danger">{{ __('Löschen') }}</button>
                </form>
                    </td>
            </tr>
        @empty
            <tr><td colspan="5" class="hint">{{ __('Noch keine Bereiche.') }}</td></tr>
        @endforelse
    </table>
    <form method="post" action="{{ route('structure.store', 'areas') }}">
        @csrf
        <input type="hidden" name="active" value="1">
        <div class="row">
            <div style="max-width:120px"><label for="new-area-sort_order">{{ __('Reihenfolge') }}</label><input type="number" id="new-area-sort_order" name="sort_order" value="{{ ($areas->max('sort_order') ?? 0) + 10 }}" min="0"></div>
            <div><label for="new-area-name">{{ __('Name') }}</label><input type="text" id="new-area-name" name="name" required placeholder="{{ __('z. B. Vertrieb') }}"></div>
            <div><label for="new-area-name_en">{{ __('Name (EN, optional)') }}</label><input type="text" id="new-area-name_en" name="name_en" placeholder="Sales"></div>
            <div style="flex:0"><button class="primary small" style="margin-top:1.75rem">{{ __('Anlegen') }}</button></div>
        </div>
    </form>
</div>

{{-- ================= Abteilungen / Departments ================= --}}
<div class="panel">
    <h2 style="margin-top:0">{{ __('Abteilungen') }} <span class="hint">({{ __('zweite Filterebene, gehören immer zu einem Bereich') }})</span></h2>
    <table class="manage">
        <tr><th style="width:90px">{{ __('Reihenfolge') }}</th><th style="width:180px">{{ __('Bereich') }}</th><th>{{ __('Name') }}</th><th style="width:70px">{{ __('Aktiv') }}</th><th></th></tr>
        @forelse($areas->flatMap(fn ($a) => $a->departments) as $dept)
            <tr>
                <form method="post" action="{{ route('structure.update', ['departments', $dept->id]) }}">
                    @csrf @method('PUT')
                    <td data-label="{{ __('Reihenfolge') }}"><input type="number" name="sort_order" value="{{ $dept->sort_order }}" min="0" max="65000" aria-label="{{ __('Reihenfolge') }}"></td>
                    <td data-label="{{ __('Bereich') }}">
                        <select name="area_id" aria-label="{{ __('Bereich') }}">
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" @selected($dept->area_id === $area->id)>{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td data-label="{{ __('Name') }}">
                        <input type="text" name="name" value="{{ $dept->name }}" required maxlength="100" aria-label="{{ __('Name') }}">
                        <input type="text" name="name_en" value="{{ $dept->name_en }}" placeholder="{{ __('Name (EN, optional)') }}" maxlength="100" aria-label="{{ __('Name (EN, optional)') }}" style="margin-top:.3rem">
                    </td>
                    <td data-label="{{ __('Aktiv') }}"><label class="choice"><input type="checkbox" name="active" value="1" @checked($dept->active) aria-label="{{ __('Aktiv') }}"></label></td>
                    <td class="actions" style="white-space:nowrap">
                        <button class="primary small">{{ __('Speichern') }}</button>
                </form>
                <form method="post" action="{{ route('structure.destroy', ['departments', $dept->id]) }}" class="inline-form"
                      onsubmit="return confirm(@js(__('Abteilung „:name“ löschen? Applikationen bleiben erhalten.', ['name' => $dept->name])))">
                    @csrf @method('DELETE')
                    <button class="primary small danger">{{ __('Löschen') }}</button>
                </form>
                    </td>
            </tr>
        @empty
            <tr><td colspan="5" class="hint">{{ __('Noch keine Abteilungen. Bitte zuerst einen Bereich anlegen.') }}</td></tr>
        @endforelse
    </table>
    @if($areas->isNotEmpty())
        <form method="post" action="{{ route('structure.store', 'departments') }}">
            @csrf
            <input type="hidden" name="active" value="1">
            <div class="row">
                <div style="max-width:120px"><label for="new-department-sort_order">{{ __('Reihenfolge') }}</label><input type="number" id="new-department-sort_order" name="sort_order" value="0" min="0"></div>
                <div style="max-width:220px"><label for="new-department-area_id">{{ __('Bereich') }}</label>
                    <select id="new-department-area_id" name="area_id">
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select></div>
                <div><label for="new-department-name">{{ __('Name') }}</label><input type="text" id="new-department-name" name="name" required placeholder="{{ __('Abteilungsname') }}"></div>
                <div><label for="new-department-name_en">{{ __('Name (EN, optional)') }}</label><input type="text" id="new-department-name_en" name="name_en"></div>
                <div style="flex:0"><button class="primary small" style="margin-top:1.75rem">{{ __('Anlegen') }}</button></div>
            </div>
        </form>
    @endif
</div>

{{-- ================= Kategorien / Categories ================= --}}
<div class="panel">
    <h2 style="margin-top:0">{{ __('Kategorien') }} <span class="hint">({{ __('Abschnitte der Startseite — Applikationen ohne Kategorie erscheinen unter „Weitere“') }})</span></h2>
    <table class="manage">
        <tr><th style="width:90px">{{ __('Reihenfolge') }}</th><th>{{ __('Name') }}</th><th class="hint" style="width:130px">{{ __('Applikationen') }}</th><th></th></tr>
        @forelse($categories as $cat)
            <tr>
                <form method="post" action="{{ route('structure.update', ['categories', $cat->id]) }}">
                    @csrf @method('PUT')
                    <td data-label="{{ __('Reihenfolge') }}"><input type="number" name="sort_order" value="{{ $cat->sort_order }}" min="0" max="65000" aria-label="{{ __('Reihenfolge') }}"></td>
                    <td data-label="{{ __('Name') }}">
                        <input type="text" name="name" value="{{ $cat->name }}" required maxlength="100" aria-label="{{ __('Name') }}">
                        <input type="text" name="name_en" value="{{ $cat->name_en }}" placeholder="{{ __('Name (EN, optional)') }}" maxlength="100" aria-label="{{ __('Name (EN, optional)') }}" style="margin-top:.3rem">
                    </td>
                    <td class="hint" data-label="{{ __('Applikationen') }}">{{ $cat->applications_count }}</td>
                    <td class="actions" style="white-space:nowrap">
                        <button class="primary small">{{ __('Speichern') }}</button>
                </form>
                <form method="post" action="{{ route('structure.destroy', ['categories', $cat->id]) }}" class="inline-form"
                      onsubmit="return confirm(@js(__('Kategorie „:name“ löschen? Die Applikationen bleiben erhalten.', ['name' => $cat->name])))">
                    @csrf @method('DELETE')
                    <button class="primary small danger">{{ __('Löschen') }}</button>
                </form>
                    </td>
            </tr>
        @empty
            <tr><td colspan="4" class="hint">{{ __('Noch keine Kategorien.') }}</td></tr>
        @endforelse
    </table>
    <form method="post" action="{{ route('structure.store', 'categories') }}">
        @csrf
        <div class="row">
            <div style="max-width:120px"><label for="new-category-sort_order">{{ __('Reihenfolge') }}</label><input type="number" id="new-category-sort_order" name="sort_order" value="{{ ($categories->max('sort_order') ?? 0) + 10 }}" min="0"></div>
            <div><label for="new-category-name">{{ __('Name') }}</label><input type="text" id="new-category-name" name="name" required placeholder="{{ __('z. B. Kommunikation') }}"></div>
            <div><label for="new-category-name_en">{{ __('Name (EN, optional)') }}</label><input type="text" id="new-category-name_en" name="name_en" placeholder="Communication"></div>
            <div style="flex:0"><button class="primary small" style="margin-top:1.75rem">{{ __('Anlegen') }}</button></div>
        </div>
    </form>
</div>
@endsection

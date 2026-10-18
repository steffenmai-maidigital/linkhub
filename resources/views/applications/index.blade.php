@extends('layouts.app')
@section('title', __('Applikationen'))
@section('content')
<h1>{{ __('Applikationen') }}</h1>
<div class="panel">
    <a class="btn" href="{{ route('applications.create') }}">{{ __('Neu anlegen') }}</a>
    <p class="hint">{{ __('Sichtbarkeit: ohne Zuordnung erscheint eine Applikation nur unter „Alle“. Zuordnungen an einen Bereich gelten für den ganzen Bereich, Zuordnungen an Abteilungen nur dort (und im Bereichs-Filter des zugehörigen Bereichs).') }}</p>
    <table>
        <tr><th>{{ __('Name') }}</th><th>{{ __('Kategorie') }}</th><th>{{ __('Sichtbarkeit') }}</th><th>{{ __('Manual') }}</th><th>{{ __('Aktiv') }}</th><th></th></tr>
        @forelse($items as $i)
            <tr>
                <td>
                    <span style="display:flex;gap:.5rem;align-items:center">
                        <span class="mono mini" style="--hue: {{ $i->hue() }}" aria-hidden="true">{{ $i->monogram() }}</span>
                        {{ $i->displayName() }}
                    </span>
                </td>
                <td>{{ $i->category?->displayName() ?? '—' }}</td>
                <td class="hint">{{ $i->visibilityLabel() !== '' ? $i->visibilityLabel() : __('nur „Alle“') }}</td>
                <td>{{ $i->manual_url ? __('ja') : '—' }}</td>
                <td>{{ $i->active ? __('ja') : __('nein') }}</td>
                <td style="white-space:nowrap">
                    <a href="{{ route('applications.edit', $i) }}">{{ __('Bearbeiten') }}</a>
                    <form method="post" action="{{ route('applications.destroy', $i) }}" class="inline-form"
                          onsubmit="return confirm('{{ __('Applikation „:name“ wirklich löschen?', ['name' => $i->name]) }}')">
                        @csrf @method('DELETE')
                        <button class="primary small danger">{{ __('Löschen') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="hint">{{ __('Noch keine Applikationen erfasst.') }}</td></tr>
        @endforelse
    </table>
    {{ $items->links() }}
</div>
@endsection

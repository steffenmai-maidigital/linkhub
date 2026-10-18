@extends('layouts.app')
@section('title', __('Audit-Log'))
@section('content')
<h1>{{ __('Audit-Log') }}</h1>
<div class="panel">
    <form method="post" action="{{ route('audit.verify') }}" class="inline-form">@csrf
        <button class="primary small">{{ __('Hash-Kette prüfen') }}</button></form>
    <span class="hint">{{ __('Jeder Eintrag ist über eine SHA-256-Hash-Kette mit seinem Vorgänger verknüpft — nachträgliche Änderungen sind damit nachweisbar.') }}</span>
    <table style="margin-top:.6rem">
        <tr>
            <th style="width:130px">{{ __('Zeit') }}</th><th style="width:130px">{{ __('Benutzer') }}</th>
            <th style="width:110px">{{ __('Modul') }}</th><th style="width:170px">{{ __('Aktion') }}</th>
            <th>{{ __('Details') }}</th><th style="width:110px">{{ __('Hash') }}</th></tr>
        @foreach($log as $entry)
            <tr>
                <td>{{ $entry->created_at->format('d.m.Y H:i:s') }}</td>
                <td>{{ $entry->causer?->name ?? __('System') }}</td>
                <td>{{ $entry->log_name }}</td>
                <td>{{ $entry->description }}</td>
                <td><code style="white-space:pre-wrap;word-break:break-word;display:block;max-height:5.6em;overflow-y:auto">{{ json_encode($entry->properties, JSON_UNESCAPED_UNICODE) }}</code></td>
                <td><code title="{{ $entry->record_hash }}">{{ substr((string) $entry->record_hash, 0, 10) }}…</code></td>
            </tr>
        @endforeach
    </table>
    {{ $log->links() }}
</div>
@endsection

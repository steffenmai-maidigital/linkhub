{{-- Felder eines eigenen Kontos (anlegen und bearbeiten); $p hält die ids der beiden Formulare auf einer Seite auseinander
     Fields of an own account (create and edit); $p keeps the ids of both forms on one page apart --}}
@php($p = $user ? 'edit-' : 'new-')
<div class="row">
    <div><label for="{{ $p }}username">{{ __('Benutzername') }}</label><input type="text" id="{{ $p }}username" name="username" value="{{ old('username', $user?->username) }}" required autocapitalize="none" spellcheck="false" placeholder="vorname.nachname"></div>
    <div><label for="{{ $p }}name">{{ __('Name') }}</label><input type="text" id="{{ $p }}name" name="name" value="{{ old('name', $user?->name) }}" required placeholder="{{ __('Vorname Nachname') }}"></div>
    <div><label for="{{ $p }}email">{{ __('E-Mail') }}</label><input type="email" id="{{ $p }}email" name="email" value="{{ old('email', $user?->email) }}" placeholder="vorname.nachname@firma.local"></div>
</div>
<label id="{{ $p }}rights-label">{{ __('Rechte') }}</label>
<div class="choice-list" role="group" aria-labelledby="{{ $p }}rights-label">
    <label class="choice"><input type="checkbox" name="is_manager" value="1" @checked(old('is_manager', $user?->is_manager))> {{ __('Manager: Applikationen, Bereiche, Abteilungen und Kategorien pflegen') }}</label>
    <label class="choice"><input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user?->is_admin))> {{ __('Administrator: zusätzlich Benutzer, Einstellungen, Audit-Log und Backup') }}</label>
</div>

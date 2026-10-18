{{-- Felder eines eigenen Kontos (anlegen und bearbeiten) / Fields of an own account (create and edit) --}}
<div class="row">
    <div><label>{{ __('Benutzername') }}</label><input type="text" name="username" value="{{ old('username', $user?->username) }}" required autocapitalize="none" spellcheck="false" placeholder="vorname.nachname"></div>
    <div><label>{{ __('Name') }}</label><input type="text" name="name" value="{{ old('name', $user?->name) }}" required placeholder="{{ __('Vorname Nachname') }}"></div>
    <div><label>{{ __('E-Mail') }}</label><input type="email" name="email" value="{{ old('email', $user?->email) }}" placeholder="vorname.nachname@firma.local"></div>
</div>
<label>{{ __('Rechte') }}</label>
<div class="choice-list">
    <label class="choice"><input type="checkbox" name="is_manager" value="1" @checked(old('is_manager', $user?->is_manager))> {{ __('Manager: Applikationen, Bereiche, Abteilungen und Kategorien pflegen') }}</label>
    <label class="choice"><input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user?->is_admin))> {{ __('Administrator: zusätzlich Benutzer, Einstellungen, Audit-Log und Backup') }}</label>
</div>

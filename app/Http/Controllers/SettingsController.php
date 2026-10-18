<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog as Activity;
use App\Models\Setting;
use App\Services\LicenseService;
use App\Support\Directory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Einstellungen (eine Seite, nummerierte Panels nach dem CrewGate-Muster):
 *   1 · Firma & Erscheinungsbild (Name ersetzt "LinkHub", Logo, Farben, Footer-Text)
 *   2 · Anmeldung (Verzeichnis: Active Directory / LDAP)
 *   3 · Rollen aus Gruppen
 *   4 · Lizenz
 *   5 · Backup
 *   6 · Fuchs-Helfer
 *   7 · Impressum und Datenschutz
 *
 * Settings (one page, numbered panels following the CrewGate pattern):
 *   1 · Company & appearance (name replaces "LinkHub", logo, colours, footer text)
 *   2 · Sign-in (directory: Active Directory / LDAP)
 *   3 · Roles from groups
 *   4 · Licence
 *   5 · Backup
 *   6 · Fox helper
 *   7 · Imprint (legal notice) and privacy policy
 */
class SettingsController extends Controller
{
    private const KEYS = [
        'brand_company_name', 'brand_header_color', 'brand_accent_color',
        'brand_footer_color', 'brand_footer_text',
        'ad_group_admins', 'ad_group_managers',
        'license_company', 'license_key',
        'backup_retention_days',
        'fox_enabled',
        'mfa_admin', 'mfa_manager', 'mfa_other', 'mfa_required_groups',
    ];

    public function index()
    {
        $settings = [];
        foreach ([...self::KEYS, 'brand_logo_path'] as $key) {
            $settings[$key] = Setting::get($key);
        }

        return view('settings.index', [
            'settings'    => $settings,
            'directory'   => Directory::settings(),
            'demo'        => (bool) config('demo.enabled'),
        ]);
    }

    public function update(Request $request)
    {
        $hex = 'regex:/^#[0-9a-fA-F]{6}$/';

        $data = $request->validate([
            'brand_company_name' => ['nullable', 'string', 'max:100'],
            'brand_header_color' => ['nullable', $hex],
            'brand_accent_color' => ['nullable', $hex],
            'brand_footer_color' => ['nullable', $hex],
            'brand_footer_text'  => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'ad_group_admins'   => ['nullable', 'string', 'max:255'],
            'ad_group_managers' => ['nullable', 'string', 'max:255'],
            'license_company' => ['nullable', 'string', 'max:150'],
            'license_key'     => ['nullable', 'string', 'max:20'],
            'backup_retention_days' => ['required', 'integer', 'between:1,3650'],
            'mfa_admin' => ['required', 'in:required,optional,off'],
            'mfa_manager' => ['required', 'in:required,optional,off'],
            'mfa_other' => ['required', 'in:required,optional,off'],
            'mfa_required_groups' => ['nullable', 'string', 'max:4000'],
        ] + Directory::rules(), [
            'ldap_user_filter.regex' => __('Der Benutzerfilter muss ein vollständiger LDAP-Filter in Klammern sein, z. B. (objectClass=person).'),
        ]);

        // Lizenzschlüssel nur nach Schema akzeptieren (1|2 + 8x Kundennr. +
        // JJMMTT Supportende + 5-stellige Anzahl)
        // Only accept licence keys matching the scheme (1|2 + 8x customer no. +
        // JJMMTT end of support + 5-digit count)
        if (filled($data['license_key'] ?? null) && ! LicenseService::parse($data['license_key'])) {
            throw ValidationException::withMessages([
                'license_key' => __('Der Lizenzschlüssel entspricht nicht dem Schema: Stelle 1 = 1 (Standard) oder 2 (Enterprise), Stellen 2–9 Kundennummer (Buchstaben/Zahlen), Stellen 10–15 Support-Ende als JJMMTT, Stellen 16–20 Lizenzanzahl.'),
            ]);
        }

        // Verzeichnis einschalten geht nur mit Server und Suchbasis
        // The directory can only be switched on with a server and search base
        if ($request->boolean('ldap_enabled') && (blank($data['ldap_host'] ?? null) || blank($data['ldap_base_dn'] ?? null))) {
            throw ValidationException::withMessages([
                'ldap_host' => __('Zum Einschalten der Verzeichnis-Anmeldung bitte Server und Suchbasis eintragen.'),
            ]);
        }

        // Impressum und Datenschutz (Standard 03)
        // Imprint (legal notice) and privacy policy (Standard 03)
        \App\Support\LegalPages::save($request);

        // Harte Untergrenze der Backup-Aufbewahrung durchsetzen
        // Enforce the hard minimum of backup retention
        $data['backup_retention_days'] = (string) max(
            (int) config('linkhub.backup_min_retention_days'),
            (int) $data['backup_retention_days']
        );
        $data['fox_enabled'] = $request->boolean('fox_enabled') ? '1' : '0';

        foreach (self::KEYS as $key) {
            Setting::set($key, $data[$key] ?? null);
        }

        // Anmeldung über das Verzeichnis (Standard 09)
        // Sign-in via the directory (Standard 09)
        Directory::save($request->only(Directory::KEYS) + ['ldap_enabled' => $request->boolean('ldap_enabled'), 'ldap_certificate' => $request->boolean('ldap_certificate')]);

        // Firmenlogo hochladen bzw. entfernen (public/uploads, per Volume persistent)
        // Upload or remove the company logo (public/uploads, persistent via volume)
        if ($request->boolean('remove_logo')) {
            $this->deleteLogo();
            Setting::set('brand_logo_path', null);
        } elseif ($request->hasFile('logo')) {
            $this->storeLogo($request->file('logo'));
        }

        activity('Einstellungen')->causedBy($request->user())->log('Einstellungen geändert');

        return back()->with('ok', __('Einstellungen gespeichert.'));
    }

    /**
     * Verbindung zum Verzeichnis mit den gespeicherten Einstellungen prüfen
     *
     * Test the connection to the directory with the saved settings
     */
    public function testLdap()
    {
        $k = Directory::settings();
        if ($k['host'] === '' || $k['base_dn'] === '') {
            return back()->withErrors(['ldap_host' => __('Bitte zuerst Server und Suchbasis speichern.')]);
        }
        try {
            $result = (new Directory($k))->test();
        } catch (\Throwable $e) {
            return back()->withErrors(['ldap_host' => __('Verbindung fehlgeschlagen: :fehler', ['fehler' => $e->getMessage()])]);
        }

        return back()->with('ok', $result['mode'] === 'template'
            ? __('Verbindung zum Verzeichnis in Ordnung: Server erreichbar, Verschlüsselung und Zertifikat passen (:ms ms). Ohne Servicekonto lässt sich die Suchbasis erst bei einer Anmeldung prüfen – bitte einmal mit einem Verzeichniskonto anmelden.', ['ms' => $result['milliseconds']])
            : __('Verbindung zum Verzeichnis in Ordnung: Servicekonto angemeldet, Suchbasis lesbar (:n Einträge geprüft, :ms ms).', [
            'n' => $result['entries'], 'ms' => $result['milliseconds'],
        ]));
    }

    /**
     * Logo ablegen. Schlaegt das fehl (z. B. fehlende Schreibrechte im
     * Upload-Volume), erscheint eine verstaendliche Meldung im Formular
     * statt einer Fehlerseite; das bisherige Logo bleibt dann erhalten.
     *
     * Store the logo. If that fails (e.g. missing write permissions in the
     * upload volume), a clear message appears in the form instead of an
     * error page; the previous logo is then kept.
     */
    private function storeLogo(\Illuminate\Http\UploadedFile $file): void
    {
        $dir = public_path('uploads');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (! is_dir($dir) || ! is_writable($dir)) {
            throw ValidationException::withMessages([
                'logo' => __('Das Logo konnte nicht gespeichert werden: der Ordner public/uploads ist nicht beschreibbar. Bitte den Container neu starten — die Rechte werden beim Start automatisch gesetzt.'),
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'png'));
        $name = 'logo-'.time().'.'.$ext;

        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'logo' => __('Das Logo konnte nicht gespeichert werden (:fehler).', ['fehler' => $e->getMessage()]),
            ]);
        }

        // Erst nach erfolgreichem Upload das alte Logo entfernen
        // Only remove the old logo after a successful upload
        $this->deleteLogo();
        Setting::set('brand_logo_path', 'uploads/'.$name);
    }

    private function deleteLogo(): void
    {
        $old = Setting::get('brand_logo_path');
        if ($old && file_exists(public_path($old))) {
            @unlink(public_path($old));
        }
    }

    /** Auditierbares Modul: alle Eingaben chronologisch / Auditable module: all entries in chronological order */
    public function auditLog(Request $request)
    {
        $log = Activity::with('causer')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('settings.audit', compact('log'));
    }

    /** Hash-Kette des Audit-Logs prüfen (Manipulationsnachweis) / Verify the audit log hash chain (tamper evidence) */
    public function auditVerify()
    {
        $result = \App\Models\ActivityLog::verifyChain();

        activity('Audit')->withProperties($result)->log('Hash-Kette geprüft');

        return back()->with('ok', $result['valid']
            ? __('Hash-Kette geprüft: :n Einträge, keine Manipulation feststellbar.', ['n' => $result['checked']])
            : __('ACHTUNG: Hash-Kette gebrochen ab Eintrag #:id — mögliche Manipulation!', ['id' => $result['first_break']]));
    }
}

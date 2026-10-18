<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Services\LicenseService;
use App\Support\ErrorLog;
use App\Support\Directory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Hilfe-Seite nach Standard 03: Kurzanleitung, System, Lizenz, Handbücher,
 * Änderungshistorie, letzte Fehler (Administratoren), Support.
 *
 * Help page per Standard 03: quick guide, system, licence, manuals,
 * change history, recent errors (administrators), support.
 */
class HelpController extends Controller
{
    /**
     * Handbücher aus docs/ — Schlüssel => [Titel, Datei, nur Admin, Kurzbeschreibung]
     *
     * Manuals from docs/ — key => [title, file, admin only, short description]
     */
    public const DOCS = [
        'user'         => ['Benutzerhandbuch', 'docs/USER-GUIDE.md', false, 'Bedienung des Portals und der Pflege'],
        'admin'        => ['Administratorhandbuch', 'docs/ADMIN-GUIDE.md', true, 'Installation, Einstellungen, Benutzer, Betrieb'],
        'architecture' => ['Systemarchitektur', 'docs/ARCHITECTURE.md', true, 'Systemübersicht und Schichtenmodell'],
        'security'     => ['Sicherheitsarchitektur', 'docs/SECURITY.md', true, 'Rollen, Audit-Hash-Kette, Datensicherung'],
        'overview'     => ['Überblick: Module & Abläufe', 'docs/DOCUMENTATION.md', false, 'Alle Module auf einen Blick'],
        'changelog'    => ['Änderungshistorie (Changelog)', 'CHANGELOG.md', false, 'Was ist in welcher Version neu'],
    ];

    /**
     * Alte Handbuch-Adressen (vor Standard 10) → neue; leiten dauerhaft um
     *
     * Old manual addresses (before standard 10) → new ones; redirect permanently
     */
    public const LEGACY_DOCS = [
        'benutzer'    => 'user',
        'architektur' => 'architecture',
        'sicherheit'  => 'security',
        'ueberblick'  => 'overview',
    ];

    public function show(Request $request)
    {
        $user = $request->user();

        return view('help', [
            'license'        => LicenseService::current(),
            'licenseCompany' => Setting::get('license_company'),
            'rawKey'         => Setting::get('license_key'),
            'usedCount'      => User::count(),
            'account'        => $user,
            'directory'      => Directory::enabled(),
            'docs'           => self::DOCS,
            'isAdmin'        => (bool) $user->is_admin,
            'recentErrors'   => $user->is_admin ? ErrorLog::recent() : null,
        ]);
    }

    /** Ein Handbuch als Seite (Markdown, HTML wird maskiert) / One manual as a page (Markdown, HTML is escaped) */
    public function doc(Request $request, string $doc)
    {
        abort_unless(isset(self::DOCS[$doc]), 404);
        [$title, $file, $adminOnly] = self::DOCS[$doc];
        abort_if($adminOnly && ! $request->user()->is_admin, 403);

        $path = base_path($file);
        abort_unless(is_file($path), 404);
        $markdown = (string) file_get_contents($path);
        try {
            $html = Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
        } catch (\Throwable) {
            $html = '<pre style="white-space:pre-wrap">' . e($markdown) . '</pre>';
        }

        return view('help-doc', ['title' => $title, 'html' => $html, 'doc' => $doc, 'docs' => self::DOCS, 'isAdmin' => (bool) $request->user()->is_admin]);
    }

    /** Administratoren: Anwendungsprotokoll leeren / Administrators: clear the application log */
    public function clearLog(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);
        ErrorLog::clear();
        activity('System')->causedBy($request->user())->log('Anwendungsprotokoll geleert');

        return redirect()->route('help')->with('ok', __('Das Protokoll wurde geleert.'));
    }
}

<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\TestSystemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Alte (deutsche) Adressen vor Standard 10 leiten dauerhaft um
// Old (German) addresses from before standard 10 redirect permanently
require __DIR__ . '/legacy.php';

// Sprache umschalten (Deutsch/Englisch), merkt sich die Wahl in der Session
// Switch language (German/English), remembers the choice in the session
Route::get('/locale/{locale}', function (\Illuminate\Http\Request $request, string $locale) {
    abort_unless(in_array($locale, ['de', 'en'], true), 404);
    session(['locale' => $locale]);
    // Zurück nur auf eigene Seiten, nie auf eine fremde Adresse aus dem Referer
    // Back only to our own pages, never to a foreign address from the referer
    $back = url()->previous();
    if (parse_url($back, PHP_URL_HOST) !== $request->getHost()) {
        $back = route('home');
    }

    return redirect()->to($back);
})->name('locale.switch');

// Öffentliches Portal (kein Login nötig, Pflege oben rechts)
// Public portal (no sign-in needed, maintenance at the top right)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Impressum und Datenschutz: öffentlich, auch ohne Anmeldung
// Imprint (legal notice) and privacy policy: public, also without sign-in
Route::get('/imprint', [LegalController::class, 'imprint'])->name('imprint');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
// Bremse gegen Passwort-Raten: 10 Versuche je Minute und Adresse (Standard 06)
// Throttle password guessing: 10 attempts per minute and address (Standard 06)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
// Zweiter Faktor (TOTP) nach Passwort bzw. Verzeichnis
// Second factor (TOTP) after password or directory
Route::get('/login/code', [AuthController::class, 'showTwoFactor'])->name('login.code');
Route::post('/login/code', [AuthController::class, 'twoFactor'])->middleware('throttle:15,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// direkt aufgerufene Abmelde-Adresse: nicht mit „Method Not Allowed“ enden
// sign-out URL called directly: do not end with "Method Not Allowed"
Route::get('/logout', fn () => redirect()->route('home'));

// Zweiter Faktor (mfa) und Startpasswort (password-changed): bis zur Erledigung nur Konto-Seite und Abmelden
// Second factor (mfa) and initial password (password-changed): until done only the account page and sign-out
Route::middleware(['auth', 'mfa', 'password-changed'])->group(function () {

    // Hilfe (Standard 03): Kurzanleitung, System, Lizenz, Handbücher, Changelog, Fehler, Support
    // Help (Standard 03): quick guide, system, licence, manuals, changelog, errors, support
    Route::get('/help', [HelpController::class, 'show'])->name('help');
    Route::get('/help/manual/{doc}', [HelpController::class, 'doc'])->name('help.doc');
    // Rechte in Middleware und Controller (Standard 06) / rights in middleware and controller (standard 06)
    Route::delete('/help/log', [HelpController::class, 'clearLog'])->middleware('admin')->name('help.clear-log');
    Route::get('/support', fn () => redirect()->route('help'));   // alte Adresse / old URL
    // Eigenes Konto: Passwort, Zwei-Faktor
    // Own account: password, two-factor
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');
    Route::post('/account/2fa', [AccountController::class, 'startTwoFactor'])->name('account.2fa.start');
    Route::post('/account/2fa/confirm', [AccountController::class, 'confirmTwoFactor'])->name('account.2fa.confirm');
    Route::post('/account/2fa/codes', [AccountController::class, 'recoveryCodes'])->name('account.2fa.codes');
    Route::delete('/account/2fa', [AccountController::class, 'disableTwoFactor'])->name('account.2fa.disable');

    // ---- Pflege: Manager und Administratoren / Maintenance: managers and administrators ----
    Route::middleware('manager')->group(function () {
        Route::resource('applications', ApplicationController::class)
            ->except(['show'])
            ->names('applications');

        Route::get('structure', [StructureController::class, 'index'])->name('structure.index');
        Route::post('structure/{type}', [StructureController::class, 'store'])->name('structure.store');
        // Nur Zahlen als Kennung: sonst endet „/structure/areas/abc“ mit Fehler 500 statt 404
        // Numbers only as id: otherwise "/structure/areas/abc" ends with error 500 instead of 404
        Route::put('structure/{type}/{id}', [StructureController::class, 'update'])->whereNumber('id')->name('structure.update');
        Route::delete('structure/{type}/{id}', [StructureController::class, 'destroy'])->whereNumber('id')->name('structure.destroy');
    });

    // ---- Verwaltung: nur Administratoren / Administration: administrators only ----
    Route::middleware('admin')->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/test-directory', [SettingsController::class, 'testLdap'])->name('settings.testldap');

        // Benutzer: eigene Konten anlegen und pflegen, Verzeichniskonten ansehen und sperren
        // Users: create and maintain own accounts, view and lock directory accounts
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::delete('users/{user}/2fa', [UserController::class, 'resetTwoFactor'])->name('users.2fa');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('audit', [SettingsController::class, 'auditLog'])->name('audit.index');
        Route::post('audit/verify', [SettingsController::class, 'auditVerify'])->name('audit.verify');

        Route::post('backup/now', [BackupController::class, 'backupNow'])->name('backup.now');
        Route::post('test-system/restore', [TestSystemController::class, 'restore'])->name('test-system.restore');
    });
});

// Unbekannte Adressen über die web-Middleware (Sitzung, Sprache) leiten, damit
// die 404-Seite in der gewählten Sprache erscheint
// Route unknown addresses through the web middleware (session, locale) so
// that the 404 page appears in the chosen language
Route::fallback(fn (\Illuminate\Http\Request $request) => \App\Support\ErrorPage::unknownAddress($request));

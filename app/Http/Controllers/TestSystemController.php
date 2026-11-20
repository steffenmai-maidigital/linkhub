<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Testsystem: letzten Produktions-Backup in die Test-Datenbank einspielen.
 * Nur aktiv, wenn TEST_SYSTEM=1 (docker-compose.test.yml) — im Testsystem
 * ist der Produktions-Backupordner read-only unter /prod-backups eingebunden.
 *
 * Test system: restore the latest production backup into the test database.
 * Only active if TEST_SYSTEM=1 (docker-compose.test.yml) — in the test system
 * the production backup folder is mounted read-only under /prod-backups.
 */
class TestSystemController extends Controller
{
    private const PROD_BACKUPS = '/prod-backups';

    public function restore(Request $request)
    {
        abort_unless(config('test.enabled'), 404);
        abort_unless($request->user()->is_admin, 403);

        $latest = $this->latestBackup();
        if (! $latest) {
            return back()->withErrors(['restore' => __('Kein Produktions-Backup gefunden. Ist der Ordner ./backups der Produktion eingebunden und existiert dort mindestens eine Sicherung?')]);
        }

        // Prüfsumme verifizieren, wenn eine .sha256-Datei daneben liegt
        // Verify the checksum if a .sha256 file lies next to it
        $sumFile = $latest.'.sha256';
        if (is_file($sumFile)) {
            $expected = trim((string) strtok((string) file_get_contents($sumFile), ' '));
            if (! hash_equals($expected, hash_file('sha256', $latest))) {
                return back()->withErrors(['restore' => __('Prüfsummen-Fehler: Die Backup-Datei :file ist beschädigt.', ['file' => basename($latest)])]);
            }
        }

        // Datenbank aus dem ZIP-Backup extrahieren — nur sie: Der App-Schlüssel der
        // Produktion (database/.app_key) bleibt im ZIP, wird weder gelesen noch
        // abgelegt; das Testsystem behält seinen eigenen Schlüssel
        // Extract the database from the ZIP backup — only that: the production
        // app key (database/.app_key) stays in the ZIP, it is neither read nor
        // stored; the test system keeps its own key
        $zip = new \ZipArchive();
        if ($zip->open($latest) !== true) {
            return back()->withErrors(['restore' => __('Backup-Datei :file konnte nicht geöffnet werden.', ['file' => basename($latest)])]);
        }
        $dbContent = $zip->getFromName('database/linkhub.sqlite');
        $zip->close();

        if ($dbContent === false) {
            return back()->withErrors(['restore' => __('Im Backup :file wurde keine Datenbank gefunden.', ['file' => basename($latest)])]);
        }

        $target = config('database.connections.sqlite.database');

        DB::disconnect();
        @unlink($target.'-wal');
        @unlink($target.'-shm');
        if (@file_put_contents($target, $dbContent) === false) {
            return back()->withErrors(['restore' => __('Backup konnte nicht kopiert werden (Rechte im Datenvolume prüfen).')]);
        }

        // Migrationen anwenden, falls das Testsystem neuer ist als die Produktion
        // Apply migrations in case the test system is newer than production
        \Artisan::call('migrate', ['--force' => true]);

        activity('Testsystem')->causedBy($request->user())
            ->withProperties(['backup' => basename($latest)])
            ->log('Produktions-Backup ins Testsystem eingespielt');

        // Die Konten sind jetzt die der Produktion: abmelden, neu anmelden. Sonst hinge
        // die Sitzung an einer Kennung, die in der neuen Datenbank jemand anderem gehört.
        // The accounts are now those of production: sign out, sign in again. Otherwise
        // the session would hang on an id that belongs to someone else in the new database.
        Auth::guard()->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('ok', __('Produktionsstand „:file“ eingespielt. Alle bisherigen Testdaten wurden ersetzt. Bitte melden Sie sich mit einem Konto der Produktion an.', ['file' => basename($latest)]));
    }

    /**
     * Neueste Backup-Datei aus dem Produktions-Backupordner (inkl. manual/)
     *
     * Newest backup file from the production backup folder (incl. manual/)
     */
    private function latestBackup(): ?string
    {
        if (! is_dir(self::PROD_BACKUPS)) {
            return null;
        }
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::PROD_BACKUPS, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.zip')) {
                $files[$f->getMTime().'-'.$f->getPathname()] = $f->getPathname();
            }
        }
        krsort($files);

        return $files ? reset($files) : null;
    }
}

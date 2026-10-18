<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            return back()->withErrors(['restore' => 'Kein Produktions-Backup gefunden. Ist der Ordner ./backups der Produktion eingebunden und existiert dort mindestens eine Sicherung?']);
        }

        // Prüfsumme verifizieren, wenn eine .sha256-Datei daneben liegt
        // Verify the checksum if a .sha256 file lies next to it
        $sumFile = $latest.'.sha256';
        if (is_file($sumFile)) {
            $expected = trim((string) strtok((string) file_get_contents($sumFile), ' '));
            if (! hash_equals($expected, hash_file('sha256', $latest))) {
                return back()->withErrors(['restore' => 'Prüfsummen-Fehler: Die Backup-Datei '.basename($latest).' ist beschädigt.']);
            }
        }

        // Datenbank aus dem ZIP-Backup extrahieren
        // Extract the database from the ZIP backup
        $zip = new \ZipArchive();
        if ($zip->open($latest) !== true) {
            return back()->withErrors(['restore' => 'Backup-Datei '.basename($latest).' konnte nicht geöffnet werden.']);
        }
        $dbContent = $zip->getFromName('database/linkhub.sqlite');
        $zip->close();

        if ($dbContent === false) {
            return back()->withErrors(['restore' => 'Im Backup '.basename($latest).' wurde keine Datenbank gefunden.']);
        }

        $target = config('database.connections.sqlite.database');

        DB::disconnect();
        @unlink($target.'-wal');
        @unlink($target.'-shm');
        if (@file_put_contents($target, $dbContent) === false) {
            return back()->withErrors(['restore' => 'Backup konnte nicht kopiert werden (Rechte im Datenvolume prüfen).']);
        }

        // Migrationen anwenden, falls das Testsystem neuer ist als die Produktion
        // Apply migrations in case the test system is newer than production
        \Artisan::call('migrate', ['--force' => true]);

        activity('Testsystem')->causedBy($request->user())
            ->withProperties(['backup' => basename($latest)])
            ->log('Produktions-Backup ins Testsystem eingespielt');

        return back()->with('ok', 'Produktionsstand "'.basename($latest).'" eingespielt. Alle bisherigen Testdaten wurden ersetzt.');
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

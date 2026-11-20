<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

/**
 * Backups der SQLite-DB, des App-Schlüssels und der Uploads (Logo).
 *
 * - Nächtliches Backup in den Backup-Ordner (Compose: ./backups)
 * - Aufbewahrung konfigurierbar, harte Untergrenze 4 Tage
 * - "Jetzt sichern": zusätzliches Backup unter backups/manual
 *   mit SHA-256-Prüfsummendatei (Manipulationsschutz); manuelle Backups
 *   werden von der automatischen Bereinigung NICHT gelöscht.
 * - Der App-Schlüssel (/data/.app_key) liegt als database/.app_key neben der
 *   Datenbank: Ohne ihn sind Servicekonto-Passwort und Zwei-Faktor-Geheimnisse
 *   nach einer Wiederherstellung auf neuer Hardware nicht lesbar. Darum ist die
 *   ZIP-Datei nur für ihren Besitzer und dessen Gruppe lesbar (0640), und
 *   Sicherungen bleiben auf dem Server: Es gibt keinen Download im Browser.
 *   Kommt einer dazu, dann ohne Schlüssel (Entscheidung vom 20.11.2026).
 *
 * Backups of the SQLite DB, the app key and the uploads (logo).
 *
 * - Nightly backup into the backup folder (Compose: ./backups)
 * - Retention configurable, hard minimum of 4 days
 * - "Jetzt sichern" (back up now): additional backup under backups/manual
 *   with a SHA-256 checksum file (tamper protection); manual backups
 *   are NOT deleted by the automatic clean-up.
 * - The app key (/data/.app_key) sits next to the database as
 *   database/.app_key: without it the service account password and the
 *   two-factor secrets cannot be read after a restore on new hardware. That is
 *   why the ZIP file is only readable by its owner and group (0640), and
 *   backups stay on the server: there is no download in the browser. Should
 *   one be added, then without the key (decision of 20.11.2026).
 */
class BackupService
{
    public function runScheduledBackup(): string
    {
        $path = $this->createArchive($this->basePath());
        $this->cleanup();
        return $path;
    }

    public function backupNow(): string
    {
        return $this->createArchive($this->basePath().'/manual');
    }

    private function basePath(): string
    {
        return rtrim(config('linkhub.backup_path', '/backups'), '/');
    }

    public function retentionDays(): int
    {
        $min = (int) config('linkhub.backup_min_retention_days', 4);
        $configured = (int) Setting::get('backup_retention_days', (string) $min);
        return max($min, $configured);
    }

    private function createArchive(string $dir): string
    {
        File::ensureDirectoryExists($dir);

        $stamp = now()->format('Ymd_His');
        $target = "$dir/linkhub_backup_{$stamp}.zip";

        // SQLite konsistent sichern: VACUUM INTO statt Dateikopie
        // Back up SQLite consistently: VACUUM INTO instead of a file copy
        $tmpDb = sys_get_temp_dir()."/linkhub_{$stamp}.sqlite";
        \DB::statement('VACUUM INTO '.\DB::getPdo()->quote($tmpDb));

        $zip = new \ZipArchive();
        $zip->open($target, \ZipArchive::CREATE);
        $zip->addFile($tmpDb, 'database/linkhub.sqlite');
        if ($key = $this->keyFile()) {
            $zip->addFile($key, 'database/.app_key');
        }
        if (File::isDirectory(public_path('uploads'))) {
            foreach (File::allFiles(public_path('uploads')) as $file) {
                $zip->addFile($file->getPathname(), 'uploads/'.$file->getRelativePathname());
            }
        }
        $zip->close();
        File::delete($tmpDb);
        // Enthält den Schlüssel: nicht für alle lesbar / holds the key: not readable by everyone
        @chmod($target, 0640);

        // Prüfsumme daneben ablegen (Manipulationsnachweis)
        // Store the checksum next to it (tamper evidence)
        File::put($target.'.sha256', hash_file('sha256', $target).'  '.basename($target).PHP_EOL);

        activity('Backup')->withProperties(['file' => basename($target)])->log('Backup erstellt');

        return $target;
    }

    /**
     * App-Schlüssel neben der Datenbank (docker/entrypoint.sh legt ihn an);
     * null, wenn APP_KEY aus der Umgebung kommt und es die Datei nicht gibt
     *
     * App key next to the database (created by docker/entrypoint.sh);
     * null if APP_KEY comes from the environment and the file does not exist
     */
    private function keyFile(): ?string
    {
        $file = dirname((string) config('database.connections.sqlite.database')) . '/.app_key';

        return is_file($file) && is_readable($file) ? $file : null;
    }

    private function cleanup(): void
    {
        $cutoff = CarbonImmutable::now()->subDays($this->retentionDays());

        foreach (File::files($this->basePath()) as $file) {
            if (CarbonImmutable::createFromTimestamp($file->getMTime())->lessThan($cutoff)) {
                File::delete($file->getPathname());
            }
        }
    }
}

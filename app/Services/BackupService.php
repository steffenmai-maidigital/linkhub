<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

/**
 * Backups der SQLite-DB und der Uploads (Logo).
 *
 * - Nächtliches Backup in den Backup-Ordner (Compose: ./backups)
 * - Aufbewahrung konfigurierbar, harte Untergrenze 4 Tage
 * - "Jetzt sichern": zusätzliches Backup unter backups/manual
 *   mit SHA-256-Prüfsummendatei (Manipulationsschutz); manuelle Backups
 *   werden von der automatischen Bereinigung NICHT gelöscht.
 *
 * Backups of the SQLite DB and the uploads (logo).
 *
 * - Nightly backup into the backup folder (Compose: ./backups)
 * - Retention configurable, hard minimum of 4 days
 * - "Jetzt sichern" (back up now): additional backup under backups/manual
 *   with a SHA-256 checksum file (tamper protection); manual backups
 *   are NOT deleted by the automatic clean-up.
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
        if (File::isDirectory(public_path('uploads'))) {
            foreach (File::allFiles(public_path('uploads')) as $file) {
                $zip->addFile($file->getPathname(), 'uploads/'.$file->getRelativePathname());
            }
        }
        $zip->close();
        File::delete($tmpDb);

        // Prüfsumme daneben ablegen (Manipulationsnachweis)
        // Store the checksum next to it (tamper evidence)
        File::put($target.'.sha256', hash_file('sha256', $target).'  '.basename($target).PHP_EOL);

        activity('Backup')->withProperties(['file' => basename($target)])->log('Backup erstellt');

        return $target;
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

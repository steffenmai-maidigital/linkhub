<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    /**
     * "Jetzt sichern": Extra-Backup mit Prüfsumme, unabhängig vom Zeitplan
     *
     * "Jetzt sichern" (back up now): extra backup with checksum, independent of the schedule
     */
    public function backupNow(Request $request, BackupService $backup)
    {
        $file = $backup->backupNow();

        activity('Backup')->causedBy($request->user())
            ->withProperties(['file' => basename($file), 'manual' => true])
            ->log('Manuelles Backup erstellt');

        return back()->with('ok', __('Backup erstellt:').' '.basename($file));
    }
}

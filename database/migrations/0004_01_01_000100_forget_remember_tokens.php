<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * „Angemeldet bleiben“ gibt es nicht mehr. Bisher konnte ein
 * Erinnerungs-Cookie (400 Tage) Verzeichnis, Sperre, Passwortwechsel und
 * Zwei-Faktor-Pflicht umgehen. Alle gespeicherten Erinnerungs-Tokens werden
 * gelöscht, damit alte Cookies ungültig sind. Idempotent; der Rückweg stellt
 * nichts wieder her (die alten Tokens sind weg, das ist gewollt).
 *
 * "Remember me" no longer exists. Until now a remember cookie (400 days)
 * could bypass the directory, the lock, password changes and mandatory
 * two-factor. All stored remember tokens are cleared so that old cookies are
 * invalid. Idempotent; the way back restores nothing (the old tokens are
 * gone, on purpose).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'remember_token')) {
            DB::table('users')->whereNotNull('remember_token')->update(['remember_token' => null]);
        }
    }

    public function down(): void
    {
        // nichts wiederherzustellen / nothing to restore
    }
};

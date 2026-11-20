<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fuchs-Helfer entfernt: Spalte users.fox (Schalter je Person) und die
 * Einstellung fox_enabled (zentraler Schalter) entfallen. Räumt auch die
 * Namen vor Standard 10 (users.fuchs, fuchs_aktiv) ab, falls eine alte
 * Sicherung zurückgespielt wurde. Idempotent. Das Audit-Log bleibt
 * unberührt. Rückweg: Die Spalte kommt leer zurück (null = Fuchs an), die
 * Einstellung fehlt und gilt damit als eingeschaltet.
 *
 * Fox helper removed: the column users.fox (per-person switch) and the
 * setting fox_enabled (central switch) are dropped. Also clears the names
 * from before standard 10 (users.fuchs, fuchs_aktiv) in case an old backup
 * was restored. Idempotent. The audit log stays untouched. Way back: the
 * column returns empty (null = fox on), the setting is missing and thus
 * counts as switched on.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            foreach (['fox', 'fuchs'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    Schema::table('users', fn (Blueprint $table) => $table->dropColumn($column));
                }
            }
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereIn('key', ['fox_enabled', 'fuchs_aktiv'])->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'fox')) {
            Schema::table('users', fn (Blueprint $table) => $table->boolean('fox')->nullable());
        }
    }
};

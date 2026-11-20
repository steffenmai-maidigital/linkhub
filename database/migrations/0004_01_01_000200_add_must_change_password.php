<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Startpasswort muss geändert werden: Spalte users.must_change_password.
 * Bestehende Konten bekommen false und sind nicht betroffen; gesetzt wird
 * sie nur, wenn LinkHub oder ein Administrator ein Startpasswort vergibt.
 * Idempotent. Rückweg: Die Spalte entfällt wieder.
 *
 * Initial password must be changed: column users.must_change_password.
 * Existing accounts get false and are not affected; it is only set when
 * LinkHub or an administrator hands out an initial password.
 * Idempotent. Way back: the column is dropped again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', fn (Blueprint $table) => $table->boolean('must_change_password')->default(false));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('must_change_password'));
        }
    }
};

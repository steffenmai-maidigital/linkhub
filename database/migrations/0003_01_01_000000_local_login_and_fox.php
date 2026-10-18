<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anmeldung mit und ohne Verzeichnis (Standard 09) und Fuchs-Helfer:
 *  - auth_source: local (eigenes Konto mit Passwort) oder ldap (Verzeichnis)
 *  - active: gesperrte Konten können sich nicht anmelden
 *  - fuchs: null = Standard (an), false = selbst ausgeblendet
 * Bestehende Konten mit AD-GUID werden als Verzeichniskonten gekennzeichnet.
 *
 * Sign-in with and without a directory (Standard 09) and fox helper:
 *  - auth_source: local (own account with password) or ldap (directory)
 *  - active: locked accounts cannot sign in
 *  - fuchs: null = default (on), false = hidden by the user
 * Existing accounts with an AD GUID are marked as directory accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'auth_source')) {
                $table->string('auth_source', 10)->default('local');
            }
            if (! Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true);
            }
            if (! Schema::hasColumn('users', 'fuchs')) {
                $table->boolean('fuchs')->nullable();
            }
        });

        DB::table('users')->whereNotNull('guid')->update(['auth_source' => 'ldap']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['auth_source', 'active', 'fuchs']);
        });
    }
};

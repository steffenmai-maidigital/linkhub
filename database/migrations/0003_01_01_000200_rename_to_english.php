<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Englische Namen (Standard 10): Spalte users.fuchs, gespeicherte Werte der
 * Zwei-Faktor-Stufen und Einstellungsschlüssel bekommen ihre englischen
 * Namen. Idempotent — läuft gefahrlos auf einer schon umgestellten Datenbank
 * und auf einer alten, zurückgespielten Sicherung. Das Audit-Log
 * (activity_log) bleibt unberührt: seine Einträge hängen in einer
 * SHA-256-Hash-Kette und werden nie geändert. Die Werte stehen hier bewusst
 * noch einmal ausgeschrieben, damit die Migration auch nach späteren
 * Änderungen am Code gleich arbeitet.
 *
 * English names (standard 10): the column users.fuchs, stored values of the
 * two-factor levels and settings keys get their English names. Idempotent —
 * runs safely on a database that is already converted and on an old restored
 * backup. The audit log (activity_log) stays untouched: its entries form a
 * SHA-256 hash chain and are never changed. The values are spelled out here
 * on purpose so that the migration keeps working the same after later code
 * changes.
 */
return new class extends Migration
{
    /** Einstellungs-Schlüssel alt => neu / settings keys old => new */
    private const SETTINGS = [
        'fuchs_aktiv'            => 'fox_enabled',
        'ldap_aktiv'             => 'ldap_enabled',
        'ldap_verschluesselung'  => 'ldap_encryption',
        'ldap_bind_passwort'     => 'ldap_bind_password',
        'ldap_bind_vorlage'      => 'ldap_bind_template',
        'ldap_benutzer_attribut' => 'ldap_user_attribute',
        'ldap_benutzer_filter'   => 'ldap_user_filter',
        'ldap_zertifikat'        => 'ldap_certificate',
        'mfa_gruppen_pflicht'    => 'mfa_required_groups',
        'mfa_sonst'              => 'mfa_other',
        'impressum_aktiv'        => 'imprint_enabled',
        'impressum_art'          => 'imprint_type',
        'impressum_text'         => 'imprint_text',
        'impressum_link'         => 'imprint_url',
        'datenschutz_aktiv'      => 'privacy_enabled',
        'datenschutz_art'        => 'privacy_type',
        'datenschutz_text'       => 'privacy_text',
        'datenschutz_link'       => 'privacy_url',
    ];

    /** Zwei-Faktor-Stufen (users.mfa_policy, Einstellungen mfa_<group>) / two-factor levels */
    private const MFA_LEVELS = ['pflicht' => 'required', 'aus' => 'off'];

    public function up(): void
    {
        $this->convert(false);
    }

    /**
     * Rückweg (so weit möglich): alte Namen und Werte wiederherstellen
     *
     * Way back (as far as possible): restore the old names and values
     */
    public function down(): void
    {
        $this->convert(true);
    }

    private function convert(bool $back): void
    {
        $flip = fn (array $map) => $back ? array_flip($map) : $map;

        // ---- Spalten / Columns ----------------------------------------------
        [$from, $to] = $back ? ['fox', 'fuchs'] : ['fuchs', 'fox'];
        if (Schema::hasTable('users') && Schema::hasColumn('users', $from) && ! Schema::hasColumn('users', $to)) {
            Schema::table('users', fn (Blueprint $t) => $t->renameColumn($from, $to));
        }

        // ---- Einstellungen / Settings ---------------------------------------
        if (Schema::hasTable('settings')) {
            foreach ($flip(self::SETTINGS) as $old => $new) {
                $this->renameSetting($old, $new);
            }
            // Stufen je Gruppe (mfa_admin, mfa_manager, mfa_other) / levels per group
            $levels = $flip(self::MFA_LEVELS);
            foreach (DB::table('settings')->where('key', 'like', 'mfa_%')->get() as $row) {
                if (in_array($row->key, ['mfa_required_groups', 'mfa_gruppen_pflicht'], true)) {
                    continue;
                }
                if (isset($levels[(string) $row->value])) {
                    DB::table('settings')->where('key', $row->key)->update(['value' => $levels[$row->value]]);
                }
            }
        }

        // ---- Benutzer / Users -------------------------------------------------
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'mfa_policy')) {
            foreach ($flip(self::MFA_LEVELS) as $old => $new) {
                DB::table('users')->where('mfa_policy', $old)->update(['mfa_policy' => $new]);
            }
        }
    }

    /**
     * Einstellung umbenennen; gibt es den neuen Schlüssel schon, gilt er und der alte entfällt
     *
     * Rename a setting; if the new key exists already it wins and the old one is removed
     */
    private function renameSetting(string $old, string $new): void
    {
        if (! DB::table('settings')->where('key', $old)->exists()) {
            return;
        }
        if (DB::table('settings')->where('key', $new)->exists()) {
            DB::table('settings')->where('key', $old)->delete();

            return;
        }
        DB::table('settings')->where('key', $old)->update(['key' => $new]);
    }
};

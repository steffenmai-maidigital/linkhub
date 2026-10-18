<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Update-Pfad von LinkHub 2.0.0: die alte Settings-Tabelle
 * (id, category, key, value) wird in das flache Schema
 * (key als Primärschlüssel, value) überführt. Gepflegte Werte werden
 * auf die neuen Schlüsselnamen übernommen. Auf Neuinstallationen
 * (keine category-Spalte vorhanden) passiert nichts.
 *
 * Hinweis: Das Firmenlogo lag in 2.0.0 im Storage und muss nach dem
 * Update einmalig neu hochgeladen werden (liegt jetzt unter public/uploads).
 *
 * Update path from LinkHub 2.0.0: the old settings table
 * (id, category, key, value) is converted into the flat schema
 * (key as primary key, value). Maintained values are carried over
 * to the new key names. On new installations
 * (no category column present) nothing happens.
 *
 * Note: in 2.0.0 the company logo was in storage and must be uploaded
 * again once after the update (it now lives under public/uploads).
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasColumn('settings', 'category')) {
            return;
        }

        // Alte Werte lesen und auf die neuen Schlüssel abbilden
        // Read old values and map them to the new keys
        $old = DB::table('settings')->pluck('value', 'key');

        $map = [
            'branding.company_name'  => 'brand_company_name',
            // 2.0.0 hatte eine Farbe für Header UND Akzent
            // 2.0.0 had one colour for header AND accent
            'branding.color_primary' => ['brand_accent_color', 'brand_header_color'],
            'ad.group_admins'        => 'ad_group_admins',
            'ad.group_managers'      => 'ad_group_managers',
            'license.company'        => 'license_company',
            'license.key'            => 'license_key',
            'backup.retention_days'  => 'backup_retention_days',
        ];

        $new = [];
        foreach ($map as $oldKey => $newKeys) {
            $value = $old[$oldKey] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            foreach ((array) $newKeys as $newKey) {
                $new[$newKey] = $value;
            }
        }

        Schema::drop('settings');
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach ($new as $key => $value) {
            DB::table('settings')->insert([
                'key' => $key, 'value' => $value,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // bewusst leer
        // intentionally empty
    }
};

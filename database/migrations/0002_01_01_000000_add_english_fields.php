<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Update-Pfad von LinkHub 2.0.0: zieht die optionalen englischen
 * Anzeigefelder auf Bestandsdatenbanken nach. Auf Neuinstallationen
 * (Spalten existieren bereits aus den Create-Migrationen) passiert nichts.
 *
 * Update path from LinkHub 2.0.0: adds the optional English display
 * fields to existing databases. On new installations
 * (columns already exist from the create migrations) nothing happens.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['areas', 'departments', 'categories'] as $table) {
            if (! Schema::hasColumn($table, 'name_en')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('name_en')->nullable()->after('name');
                });
            }
        }

        if (! Schema::hasColumn('applications', 'name_en')) {
            Schema::table('applications', function (Blueprint $t) {
                $t->string('name_en')->nullable()->after('name');
            });
        }
        if (! Schema::hasColumn('applications', 'description_en')) {
            Schema::table('applications', function (Blueprint $t) {
                $t->text('description_en')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        // bewusst leer: Spalten bleiben erhalten
        // intentionally empty: columns are kept
    }
};

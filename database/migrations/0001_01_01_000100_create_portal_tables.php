<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Bereiche: oberste Filterebene des Portals
        // Areas: top filter level of the portal
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();   // optionale englische Anzeige / optional English display
            $table->string('slug')->unique();      // für Filter-Links (?bereich=…) / for filter links
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Abteilungen: zweite Filterebene, gehören immer zu einem Bereich
        // Departments: second filter level, always belong to an area
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['area_id', 'slug']);
        });

        // Kategorien: optionale, pflegearme Abschnitte auf der Startseite
        // Categories: optional, low-maintenance sections on the start page
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('areas');
    }
};

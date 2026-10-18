<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();          // optionale englische Anzeige / optional English display
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('url');                       // Link zur Applikation / link to the application
            $table->string('manual_url')->nullable();    // Link zum Manual / link to the manual
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Sichtbarkeit: direkt am Bereich (gilt für den ganzen Bereich) …
        // Visibility: directly on the area (applies to the whole area) …
        Schema::create('application_area', function (Blueprint $table) {
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->primary(['application_id', 'area_id']);
        });

        // … und/oder an einzelnen Abteilungen
        // … and/or on individual departments
        Schema::create('application_department', function (Blueprint $table) {
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->primary(['application_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_department');
        Schema::dropIfExists('application_area');
        Schema::dropIfExists('applications');
    }
};

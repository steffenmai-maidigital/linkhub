<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelle für spatie/laravel-activitylog (v4) — im Repo enthalten,
 * damit der Container ohne vendor:publish direkt startet.
 * record_hash: SHA-256-Hash-Kette (Manipulationsnachweis, siehe
 * App\Models\ActivityLog).
 *
 * Table for spatie/laravel-activitylog (v4) — included in the repo
 * so that the container starts directly without vendor:publish.
 * record_hash: SHA-256 hash chain (tamper evidence, see
 * App\Models\ActivityLog).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('record_hash', 64)->nullable()->index();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};

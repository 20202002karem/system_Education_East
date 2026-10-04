<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B7 — settings_history. Every PATCH /settings/{key} writes a row here.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings_history', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100);
            $table->string('old_value', 255)->nullable();
            $table->string('new_value', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('changed_at');

            $table->index(['key', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings_history');
    }
};

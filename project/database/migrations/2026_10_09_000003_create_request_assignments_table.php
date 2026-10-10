<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — request_assignments. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('request_cycles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('assignee_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('reason', 255)->nullable();
            $table->dateTime('from');
            $table->dateTime('to')->nullable();

            $table->index('cycle_id', 'idx_assignments_cycle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_assignments');
    }
};

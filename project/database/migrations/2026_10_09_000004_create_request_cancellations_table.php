<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — request_cancellations. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('reason_category', 60);
            $table->text('reason_text');
            $table->text('work_done_summary')->nullable();
            $table->foreignId('cancelled_by')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('stage', ['before_assignment', 'after_start']);
            $table->dateTime('cancelled_at');

            $table->unique('request_id', 'uq_cancellations_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_cancellations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — request_cycles. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('cycle_no');
            $table->enum('status', ['new', 'triaged', 'assigned', 'in_progress', 'held', 'reopened_pending_assignment', 'closed', 'cancelled'])
                ->default('new');
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->string('closure_action', 255)->nullable();
            $table->string('closure_result', 255)->nullable();
            $table->unsignedInteger('closure_effort_minutes')->nullable();
            $table->string('hold_reason', 255)->nullable();
            // OI-DB-M3-07 (MINIMAL COMPATIBLE FIX): 1 while the cycle is non-terminal, NULL once
            // closed/cancelled. UNIQUE(request_id, open_marker) = "one open cycle per request",
            // portable across MySQL and sqlite (NULLs never collide).
            $table->unsignedTinyInteger('open_marker')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['request_id', 'cycle_no'], 'uq_cycles_request_cycleno');
            $table->unique(['request_id', 'open_marker'], 'uq_cycles_one_open');
            $table->index(['assignee_user_id', 'status'], 'idx_cycles_assignee_status');
            $table->index('status', 'idx_cycles_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_cycles');
    }
};

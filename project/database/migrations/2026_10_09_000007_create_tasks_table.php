<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — tasks. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no', 30)->unique('uq_tasks_ref_no');
            $table->foreignId('task_type_id')->constrained('task_types')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->foreignId('request_cycle_id')->nullable()->constrained('request_cycles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('site_id')->nullable()->constrained('sites')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('status', ['new', 'assigned', 'in_progress', 'held', 'completed', 'cancelled'])->default('new');
            $table->dateTime('due_at')->nullable();
            $table->text('result_summary')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['assignee_id', 'status'], 'idx_tasks_assignee_status');
            $table->index('request_cycle_id', 'idx_tasks_cycle');
            $table->index('due_at', 'idx_tasks_due');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

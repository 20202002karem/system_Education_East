<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — proposals. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['reassign', 'cancel']);
            $table->enum('subject_type', ['request', 'task']);
            $table->unsignedBigInteger('subject_id'); // polymorphic, no FK (DBD-M3-01)
            $table->foreignId('proposer_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->text('reason');
            $table->text('work_done_summary')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->dateTime('decided_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id'], 'idx_proposals_subject');
            $table->index('status', 'idx_proposals_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};

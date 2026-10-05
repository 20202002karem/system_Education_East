<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M2 Batch 2 §9.2 — append-only (single timestamp, no updated_at).
return new class extends Migration
{
    public function up(): void
    {
        $statuses = ['working', 'under_maintenance', 'broken', 'stored', 'in_transfer', 'decommissioned'];
        Schema::create('asset_status_history', function (Blueprint $table) use ($statuses) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->enum('from_status', $statuses);
            $table->enum('to_status', $statuses);
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['asset_id', 'changed_at'], 'idx_status_history_asset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_status_history');
    }
};

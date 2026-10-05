<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M2 Batch 2 §9.4 — append-only; UNIQUE(asset_id, legacy_number) (BR-M2-05, Proposed).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_legacy_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->string('legacy_number', 60);
            $table->string('source', 100)->nullable();
            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['asset_id', 'legacy_number'], 'uq_legacy_asset_number');
            $table->index('asset_id', 'idx_legacy_asset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_legacy_numbers');
    }
};

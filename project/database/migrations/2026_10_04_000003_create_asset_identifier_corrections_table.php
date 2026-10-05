<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M2 Batch 2 §9.3 — append-only; reason NOT NULL (BR-M2-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_identifier_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->enum('field_name', ['inventory_no', 'serial_no']);
            $table->string('old_value', 60);
            $table->string('new_value', 60);
            $table->foreignId('corrected_by')->constrained('users')->restrictOnDelete();
            $table->string('reason', 255);
            $table->timestamp('corrected_at')->useCurrent();

            $table->index('asset_id', 'idx_corrections_asset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_identifier_corrections');
    }
};

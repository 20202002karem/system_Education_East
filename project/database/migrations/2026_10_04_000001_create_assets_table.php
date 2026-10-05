<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M2 Batch 2 §9.1 — assets. No deleted_at, no created_by/updated_by (per Batch 2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_no', 30)->unique('uq_assets_inventory_no');
            $table->string('serial_no', 60)->nullable()->unique('uq_assets_serial_no');
            $table->foreignId('category_id')->constrained('device_categories')->restrictOnDelete();
            $table->foreignId('current_site_id')->constrained('sites')->restrictOnDelete();
            $table->enum('status', ['working', 'under_maintenance', 'broken', 'stored', 'in_transfer', 'decommissioned'])
                ->default('working');
            $table->string('holder_text', 150)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('category_id', 'idx_assets_category');
            $table->index('status', 'idx_assets_status');
            $table->index(['current_site_id', 'status'], 'idx_assets_site_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

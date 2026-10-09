<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — requests. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no', 30)->unique('uq_requests_ref_no');
            $table->foreignId('origin_site_id')->constrained('sites')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('requester_name', 150)->nullable();
            $table->foreignId('registered_by_user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('channel_id')->constrained('intake_channels')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('request_type_id')->nullable()->constrained('request_types')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('suggested_priority', ['emergency', 'high', 'normal', 'low'])->nullable();
            $table->enum('priority', ['emergency', 'high', 'normal', 'low'])->nullable();
            $table->text('description');
            $table->unsignedInteger('current_cycle_no')->default(1);
            $table->unsignedInteger('reopen_count')->default(0);
            $table->timestamps();

            $table->index('origin_site_id', 'idx_requests_origin_site');
            $table->index('asset_id', 'idx_requests_asset');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B12 — intake_channels: simple schema-only reference list. No FK from this
// table since the M2/M3 tables that will consume it are out of M1 scope.
// Managed by the chairman via /reference/*.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_channels');
    }
};

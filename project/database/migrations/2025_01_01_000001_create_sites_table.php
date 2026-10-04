<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B1 — sites (ORG-01). [معتمد]: type, code (UNIQUE), name_ar, status
// (archive only — IN-01, no physical delete).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['school', 'department', 'warehouse']);
            $table->string('code', 30)->unique();
            $table->string('name_ar', 150);
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B9 — audit_chain_checks (M1 criterion 5). Filled by a daily
// scheduled job that walks audit_log verifying the hash chain.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_chain_checks', function (Blueprint $table) {
            $table->id();
            $table->dateTime('run_at');
            $table->unsignedBigInteger('from_seq');
            $table->unsignedBigInteger('to_seq');
            $table->enum('result', ['ok', 'broken']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_chain_checks');
    }
};

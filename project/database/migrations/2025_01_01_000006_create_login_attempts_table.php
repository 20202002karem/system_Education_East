<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B6 — login_attempts. Drives the failed-attempt counter for the
// 15-minute lockout (MD-01 / M1 criterion 8).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attempted_identifier', 150);
            $table->boolean('success');
            $table->string('ip', 45)->nullable();
            $table->dateTime('attempted_at');

            $table->index(['attempted_identifier', 'attempted_at']);
            $table->index(['user_id', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};

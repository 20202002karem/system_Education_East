<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B6 — sessions. Tracks last_activity_at / expires_at per issued
// Sanctum token so we can enforce the 30-minute inactivity rule and bulk
// "terminate all sessions" (E-08). id = the Sanctum personal_access_tokens.id.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('last_activity_at');
            $table->dateTime('expires_at');
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};

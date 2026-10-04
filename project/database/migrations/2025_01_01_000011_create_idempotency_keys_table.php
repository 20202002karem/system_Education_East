<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B10 — idempotency_keys (IN-11 / M1 criterion 6). Required header
// on every resource-creating POST (Batch 3 §1).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('key', 100);
            $table->json('response_snapshot')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->dateTime('created_at');

            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};

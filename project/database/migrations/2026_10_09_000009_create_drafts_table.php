<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — drafts. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('form_type', ['request_create', 'note', 'closure', 'cancellation', 'proposal']);
            $table->string('form_key', 60)->nullable();
            $table->json('payload');
            $table->timestamp('updated_at')->useCurrent();

            $table->index(['user_id', 'form_type'], 'idx_drafts_user_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drafts');
    }
};

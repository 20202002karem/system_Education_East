<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — request_notes. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('request_cycles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('visibility', ['internal', 'external']);
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cycle_id', 'created_at'], 'idx_notes_cycle_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_notes');
    }
};

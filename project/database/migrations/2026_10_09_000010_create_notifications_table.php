<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — notifications. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('event_type', 60);
            $table->enum('source_type', ['request', 'task']);
            $table->unsignedBigInteger('source_id'); // polymorphic, no FK (DBD-M3-01)
            $table->string('message', 255);
            $table->dateTime('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['recipient_id', 'read_at'], 'idx_notifications_recipient_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3 Batch 2 — attachments. Append-only / no physical delete (IN-01) except drafts (DBD-M3-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->enum('owner_type', ['request', 'request_note', 'task']);
            $table->unsignedBigInteger('owner_id'); // polymorphic, no FK (DBD-M3-01)
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('storage_path', 255);
            $table->string('sha256', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type', 100);
            $table->string('original_filename', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['owner_type', 'owner_id'], 'idx_attachments_owner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};

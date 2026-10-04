<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B8 — audit_log (AUD-01/02, M1 criterion 5). Intentionally has NO
// foreign keys to operational tables (schema independence, per stage 3-b,
// unmodified by later docs). Application layer performs INSERT only — no
// UPDATE/DELETE is ever issued against this table (IN-12), and no route
// exists for modifying or deleting rows (Batch 3 §2.5). Hash-chained via
// prev_hash/hash (IN-13). Never store secrets (passwords, mfa_secret, tokens)
// in `before`/`after`/`reason`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->bigIncrements('seq'); // ordering guarantee for the hash chain
            $table->dateTime('occurred_at');
            $table->unsignedBigInteger('actor_id'); // no FK by design
            $table->string('action', 60);
            $table->string('entity_type', 60);
            $table->string('entity_id', 60);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('reason', 255)->nullable();
            $table->enum('source', ['web', 'mobile', 'system'])->default('web');
            $table->string('ip', 45)->nullable();
            $table->json('flags')->nullable(); // e.g. self_approval / delegated / late_approval / admin_completion — M4 concepts, unused in M1 but column belongs to this M1 table
            $table->string('prev_hash', 64);
            $table->string('hash', 64);

            $table->index(['entity_type', 'entity_id']);
            $table->index(['actor_id']);
            $table->index(['occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};

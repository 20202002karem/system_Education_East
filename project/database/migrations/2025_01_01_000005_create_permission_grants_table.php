<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B5 — permission_grants (AUTH-08). New row per grant (never updated
// in place); revoke closes the row via revoked_by/revoked_at. create_request
// is intentionally excluded from the permission_key enum (reserved/disabled, D-22b).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('permission_key', ['initiate_transfer', 'initiate_decommission', 'edit_assets']);
            $table->foreignId('granted_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->dateTime('granted_at');
            $table->foreignId('revoked_by')->nullable()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->dateTime('revoked_at')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'permission_key', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_grants');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B3 — users (AUTH-01..09).
// login_identifier decision (resolved by product owner, superseding Batch 2's
// "[مفتوح]"): login_identifier = email. Must be a valid, unique email address.
// mfa_secret decision (resolved by product owner, closing Batch 3 §9 item 2 /
// §7 gap): adds users.mfa_secret, encrypted at rest, never exposed in any API
// response, never logged, never written to audit_log.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->unique(); // login_identifier = email
            $table->string('password_hash', 255);
            $table->enum('role', ['school_manager', 'chairman', 'secretary', 'engineer', 'technician']);
            $table->enum('view_scope', ['own_site', 'sites', 'all', 'assigned_only']);
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->boolean('mfa_enabled')->default(false);
            // TOTP secret, encrypted via Laravel's `encrypted` cast (AES-256 using APP_KEY).
            $table->text('mfa_secret')->nullable();
            $table->string('specialization', 150)->nullable(); // ORG-02, free text per Batch 2 §B3
            $table->unsignedInteger('failed_login_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

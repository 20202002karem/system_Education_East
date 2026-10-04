<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B7 — settings. Editable key/value store for the tunable values in
// D-21 (reopen window, approval validity, transfer reminder, late approval
// window) plus MD-01 password/lockout policy values. attachment_size_limit
// (D-34) exists as a row but stays NULL until D-34 is resolved (Pending).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->string('value', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

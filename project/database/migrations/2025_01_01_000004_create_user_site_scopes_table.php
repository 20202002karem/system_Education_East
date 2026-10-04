<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B4 — user_site_scopes (D-04). Used only when users.view_scope='sites'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_site_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_site_scopes');
    }
};

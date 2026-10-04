<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B2 — site_managers (ORG-01/ORG-03). "One active manager per site"
// is enforced in the application service layer inside a DB transaction
// (Batch 3 §3.5: assigning a new manager closes the previous open row).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('from');
            $table->date('to')->nullable(); // NULL = currently active
            $table->timestamps();

            $table->index(['site_id', 'to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_managers');
    }
};

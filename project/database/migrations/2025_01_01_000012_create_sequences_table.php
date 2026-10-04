<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B11 — sequences (D-33). Infrastructure table for M1; not yet
// consumed by any M1 endpoint (reference numbers belong to M2/M3 entities),
// created now as shared infrastructure per the M1 table inventory.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('scope', 20);
            $table->smallInteger('year');
            $table->unsignedBigInteger('last_value')->default(0);

            $table->primary(['scope', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};

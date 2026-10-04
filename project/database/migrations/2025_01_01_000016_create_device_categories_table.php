<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch 2 §B12 — device_categories. Appendices v1.0 §4.2 names only `name`;
// older fields from stage 3-b (has_serial, parent_id) were NOT carried into
// the current Appendices and are intentionally omitted here per Batch 2's
// explicit note that "the newer Appendices is the binding reference."
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_categories');
    }
};

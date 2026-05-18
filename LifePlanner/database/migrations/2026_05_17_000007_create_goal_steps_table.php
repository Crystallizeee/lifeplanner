<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// @see LP-DB-SCHEMA-2026-001 | Productivity Module — goal_steps
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->string('step_name', 255);
            $table->date('due_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            // @see LP-DB-SCHEMA-2026-001 | Section 04 — Progress calculation index
            $table->index(['goal_id', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_steps');
    }
};

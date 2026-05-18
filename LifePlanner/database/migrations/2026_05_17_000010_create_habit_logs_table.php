<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// @see LP-DB-SCHEMA-2026-001 | Habit Module — habit_logs
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_checked')->default(false);
            $table->timestamps();

            // @see LP-DB-SCHEMA-2026-001 | Section 04 — Prevent duplicate & streak calc
            $table->unique(['habit_id', 'date']);
            $table->index(['habit_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_logs');
    }
};

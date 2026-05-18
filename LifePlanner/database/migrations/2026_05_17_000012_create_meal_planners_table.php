<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// @see LP-DB-SCHEMA-2026-001 | Health Module — meal_planners
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_planners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('meal_time', ['breakfast', 'lunch', 'dinner', 'snack']);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            // @see LP-DB-SCHEMA-2026-001 | Section 04 — One slot per meal per day
            $table->unique(['user_id', 'date', 'meal_time']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_planners');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// @see LP-DB-SCHEMA-2026-001 | Habit Module — habits
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('habit_name', 100);
            $table->string('emoji', 10)->nullable();
            $table->boolean('is_archived')->default(false);
            $table->smallInteger('current_streak')->unsigned()->default(0); // Computed cache
            $table->smallInteger('longest_streak')->unsigned()->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};

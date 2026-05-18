<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// @see LP-DB-SCHEMA-2026-001 | Productivity Module — goals
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('why')->nullable();
            $table->text('challenges')->nullable();
            $table->string('reward', 200)->nullable();
            $table->enum('status', ['active', 'completed', 'archived'])->default('active');
            $table->decimal('progress_pct', 5, 2)->default(0); // Computed from goal_steps
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};

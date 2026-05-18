<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// @see LP-DB-SCHEMA-2026-001 | Productivity Module — todo_lists
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('task_name', 255);
            $table->enum('status', ['todo', 'in_progress', 'hold', 'done', 'canceled'])->default('todo');
            $table->enum('priority', ['very_high', 'high', 'medium', 'low'])->default('medium');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // @see LP-DB-SCHEMA-2026-001 | Section 04 — Today's Focus & Kanban indexes
            $table->index(['user_id', 'due_date']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_lists');
    }
};

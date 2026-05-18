<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// @see LP-DB-SCHEMA-2026-001 | Finance Module — transactions
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('savings_goal_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'bill', 'saving']);
            $table->decimal('amount', 15, 2)->unsigned();
            $table->string('description', 255);
            $table->date('transaction_date');
            $table->date('due_date')->nullable(); // For bills only
            $table->enum('status', ['planned', 'paid', 'overdue'])->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();

            // @see LP-DB-SCHEMA-2026-001 | Section 04 — Recommended Indexes
            $table->index(['budget_id', 'type']);
            $table->index(['user_id', 'transaction_date']);
            $table->index(['status', 'due_date']);
            $table->index('savings_goal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

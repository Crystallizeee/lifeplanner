<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// @see LP-DB-SCHEMA-2026-001 | Finance Module — budget_allocations
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2)->unsigned();
            $table->timestamps();

            $table->unique(['budget_id', 'category_id']); // One allocation per category per budget period
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_allocations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// @see LP-DB-SCHEMA-2026-001 | Health Module — grocery_lists
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grocery_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start'); // Monday of the week
            $table->string('item_name', 150);
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 30)->nullable(); // gram, buah, liter, bungkus
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_bought')->default(false);
            $table->timestamps();

            // @see LP-DB-SCHEMA-2026-001 | Section 04 — Weekly grocery lookup
            $table->index(['user_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_lists');
    }
};

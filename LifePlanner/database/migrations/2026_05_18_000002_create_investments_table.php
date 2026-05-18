<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('asset_name', 150);
            $table->enum('asset_type', ['saham', 'reksadana', 'crypto', 'emas', 'deposito', 'properti', 'lainnya']);
            $table->decimal('quantity', 18, 8); // Supports fractional crypto units
            $table->decimal('buy_price', 15, 2)->unsigned();
            $table->decimal('current_price', 15, 2)->unsigned();
            $table->date('buy_date');
            $table->text('notes')->nullable();
            $table->boolean('is_sold')->default(false);
            $table->decimal('sold_price', 15, 2)->nullable();
            $table->date('sold_date')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'asset_type']);
            $table->index(['user_id', 'is_sold']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};

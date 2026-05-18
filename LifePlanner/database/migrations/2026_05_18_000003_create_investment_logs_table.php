<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('action', ['buy', 'sell', 'dividend', 'price_update', 'top_up']);
            $table->decimal('quantity', 18, 8)->nullable();
            $table->decimal('price', 15, 2);
            $table->text('notes')->nullable();
            $table->dateTime('logged_at');
            $table->timestamps();

            $table->index(['investment_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_logs');
    }
};

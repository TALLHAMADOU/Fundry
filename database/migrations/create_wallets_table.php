<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('currency_id')->constrained()->onDelete('restrict');
            $table->string('name');
            $table->string('type'); // personal, business, savings, investment, government
            $table->decimal('balance', 20, 8)->default(0);
            $table->decimal('max_balance', 20, 8)->nullable();
            $table->decimal('min_balance', 20, 8)->nullable();
            $table->decimal('transaction_limit', 20, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('security_rules')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('currency_id');
            $table->index('type');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};

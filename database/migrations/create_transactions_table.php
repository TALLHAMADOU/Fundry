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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->foreignId('currency_id')->constrained()->onDelete('restrict');
            $table->string('type'); // deposit, withdrawal, transfer, exchange, fee, refund
            $table->string('status'); // pending, completed, failed
            $table->decimal('amount', 20, 8);
            $table->decimal('converted_amount', 20, 8)->nullable();
            $table->decimal('exchange_rate', 20, 10)->nullable();
            $table->string('reference')->unique();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('from_wallet_id');
            $table->index('to_wallet_id');
            $table->index('currency_id');
            $table->index('type');
            $table->index('status');
            $table->index('reference');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

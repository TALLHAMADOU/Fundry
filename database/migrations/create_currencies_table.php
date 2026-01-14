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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->after('id')->constrained('countries')->onDelete('set null');
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('iso_code', 3)->unique(); // Code ISO 4217 standardisé
            $table->string('type'); // fiat, crypto, device
            $table->string('symbol', 10);
            $table->decimal('exchange_rate', 20, 10)->default(1.0);
            $table->boolean('base_currency')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('decimals')->default(2);
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('iso_code');
            $table->index('country_id');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};

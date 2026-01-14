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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom du pays en français
            $table->string('name_en'); // Nom du pays en anglais
            $table->string('iso_code', 2)->unique(); // Code ISO 3166-1 alpha-2 (ex: FR, US, SN)
            $table->string('iso_code_3', 3)->unique()->nullable(); // Code ISO 3166-1 alpha-3 (ex: FRA, USA, SEN)
            $table->string('numeric_code', 3)->nullable(); // Code numérique ISO 3166-1 (ex: 250, 840, 686)
            $table->string('phone_code', 10)->nullable(); // Code téléphonique (ex: +33, +1, +221)
            $table->string('continent')->nullable(); // Continent
            $table->string('capital')->nullable(); // Capitale
            $table->string('currency_code', 3)->nullable(); // Code devise ISO 4217 (ex: EUR, USD, XOF)
            $table->string('currency_name')->nullable(); // Nom de la devise
            $table->string('currency_symbol', 10)->nullable(); // Symbole de la devise
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('iso_code');
            $table->index('currency_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};

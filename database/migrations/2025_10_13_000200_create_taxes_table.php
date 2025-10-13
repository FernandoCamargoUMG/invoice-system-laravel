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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del impuesto (ej: IVA, VAT, GST)
            $table->string('country', 2)->nullable(); // Código de país ISO 3166-1 alfa-2 (ej: GT, US, MX)
            $table->decimal('rate', 6, 4); // Tasa decimal (ej: 0.1200 para 12%)
            $table->boolean('included_in_price')->default(false); // true si el precio ya incluye el impuesto
            $table->enum('applies_to', ['all', 'sales', 'purchases', 'products'])->default('all');
            $table->string('currency', 3)->nullable(); // Moneda ISO 4217 (opcional)
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};

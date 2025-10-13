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
            $table->string('name'); // Nombre del impuesto (IVA, VAT, GST, etc.)
            $table->string('country', 2)->nullable(); // Código de país ISO (GT, US, MX, etc.)
            $table->decimal('rate', 6, 4); // Tasa (ej: 0.1200)
            $table->boolean('included_in_price')->default(false); // Si el precio ya incluye el impuesto
            $table->enum('applies_to', ['all', 'sales', 'purchases', 'products'])->default('all');
            $table->string('currency', 3)->nullable(); // Moneda (opcional)
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

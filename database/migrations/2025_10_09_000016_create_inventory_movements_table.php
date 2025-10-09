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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'return']); // Tipo de movimiento
            $table->bigInteger('quantity'); // Positivo para entrada, negativo para salida
            $table->bigInteger('stock_before'); // Stock antes del movimiento
            $table->bigInteger('stock_after'); // Stock después del movimiento
            $table->string('reference_type')->nullable(); // 'purchase', 'invoice', 'quote', 'manual'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID de la referencia
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id'); // Usuario que hizo el movimiento
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
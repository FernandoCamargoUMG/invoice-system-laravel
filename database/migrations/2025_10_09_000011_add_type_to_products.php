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
        Schema::table('products', function (Blueprint $table) {
            $table->enum('type', ['product', 'service'])->default('product')->after('name');
            $table->string('sku', 50)->nullable()->after('type'); // Código de producto
            $table->decimal('cost_price', 10, 2)->nullable()->after('price'); // Precio de costo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'sku', 'cost_price']);
        });
    }
};
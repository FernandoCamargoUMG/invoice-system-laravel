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
        // Actualizar campo stock en products de integer a bigint
        Schema::table('products', function (Blueprint $table) {
            $table->bigInteger('stock')->default(0)->change();
        });

        // Actualizar campo quantity en invoice_items de integer a bigint
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->bigInteger('quantity')->change();
        });

        // Actualizar campos quantity en purchase_items de integer a bigint
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->bigInteger('quantity')->change();
        });

        // Actualizar campos quantity en quote_items de integer a bigint
        Schema::table('quote_items', function (Blueprint $table) {
            $table->bigInteger('quantity')->change();
        });

        // Actualizar campos en inventory_movements de integer a bigint
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->bigInteger('quantity')->change();
            $table->bigInteger('stock_before')->change();
            $table->bigInteger('stock_after')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->integer('stock_before')->change();
            $table->integer('stock_after')->change();
        });
    }
};
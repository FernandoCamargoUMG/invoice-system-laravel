<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Añadir índices para optimización
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index(['invoice_id', 'product_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['invoice_id', 'payment_date']);
            $table->index('payment_date');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('stock');
            $table->index(['name', 'stock']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['invoice_id', 'product_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['invoice_id', 'payment_date']);
            $table->dropIndex(['payment_date']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['stock']);
            $table->dropIndex(['name', 'stock']);
        });
    }
};
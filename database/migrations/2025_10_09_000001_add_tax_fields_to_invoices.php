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
        Schema::table('invoices', function (Blueprint $table) {
            // Agregar campos faltantes del sistema PHP vanilla
            $table->decimal('subtotal', 10, 2)->default(0)->after('total');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
            $table->decimal('tax_rate', 5, 4)->default(0.12)->after('tax_amount'); // 12% por defecto
            $table->decimal('balance_due', 10, 2)->default(0)->after('tax_rate'); // Saldo pendiente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'tax_amount', 'tax_rate', 'balance_due']);
        });
    }
};
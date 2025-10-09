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
        // Esta migración registra que todos los IDs y foreign keys 
        // fueron actualizados a BIGINT usando el script convert_to_bigint.php
        // 
        // Cambios realizados:
        // - Todos los campos 'id' de INT a BIGINT UNSIGNED AUTO_INCREMENT
        // - Todos los foreign keys de INT a BIGINT UNSIGNED
        // - Foreign keys recreadas correctamente
        // 
        // Tablas afectadas:
        // users, customers, products, suppliers, invoices, invoice_items, 
        // payments, purchases, purchase_items, quotes, quote_items, inventory_movements
        
        // No hay código SQL aquí porque los cambios ya fueron aplicados manualmente
        // Esta migración solo sirve para registro histórico
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es posible hacer rollback automático de esta migración
        // Los cambios fueron aplicados manualmente usando SQL directo
        throw new \Exception('No es posible hacer rollback automático de esta migración. Los cambios fueron aplicados manualmente.');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Agregar foreign keys solo si no existen
            if (!$this->foreignKeyExists('purchases', 'purchases_supplier_id_foreign')) {
                $table->foreign('supplier_id', 'purchases_supplier_id_foreign')->references('id')->on('suppliers')->onDelete('cascade');
            }
            if (!$this->foreignKeyExists('purchases', 'purchases_user_id_foreign')) {
                $table->foreign('user_id', 'purchases_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    private function foreignKeyExists($table, $keyName)
    {
        try {
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '{$table}' AND CONSTRAINT_NAME = '{$keyName}' AND TABLE_SCHEMA = DATABASE()");
            return count($foreignKeys) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Eliminar foreign keys e índices
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['purchase_date']);
        });
    }
};

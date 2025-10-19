<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('quotes')) {
            // Use raw statement to avoid requiring doctrine/dbal for column modification
            DB::statement("ALTER TABLE `quotes` MODIFY `total` DECIMAL(10,2) NOT NULL DEFAULT 0;");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quotes')) {
            // Revert to NOT NULL without default (best effort)
            DB::statement("ALTER TABLE `quotes` MODIFY `total` DECIMAL(10,2) NOT NULL;");
        }
    }
};

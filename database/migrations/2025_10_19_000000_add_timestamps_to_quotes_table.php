<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add nullable timestamps to quotes table.
     */
    public function up(): void
    {
        if (Schema::hasTable('quotes')) {
            Schema::table('quotes', function (Blueprint $table) {
                if (!Schema::hasColumn('quotes', 'created_at')) {
                    $table->timestamp('created_at')->nullable()->after('quote_date');
                }
                if (!Schema::hasColumn('quotes', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        if (Schema::hasTable('quotes')) {
            Schema::table('quotes', function (Blueprint $table) {
                if (Schema::hasColumn('quotes', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
                if (Schema::hasColumn('quotes', 'created_at')) {
                    $table->dropColumn('created_at');
                }
            });
        }
    }
};

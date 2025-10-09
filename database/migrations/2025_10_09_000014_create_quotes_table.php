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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id');
            $table->string('quote_number', 20)->unique(); // Número de cotización correlativo
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0.12);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected', 'converted', 'expired'])->default('draft');
            $table->date('quote_date');
            $table->date('valid_until'); // Fecha de vencimiento
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('converted_invoice_id')->nullable(); // Si se convirtió a factura
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
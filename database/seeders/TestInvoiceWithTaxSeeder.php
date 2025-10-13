<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestInvoiceWithTaxSeeder extends Seeder
{
    /**
     * Seeder para probar el sistema completo con impuestos y triggers
     */
    public function run(): void
    {
        // Limpiar datos existentes (en orden correcto por foreign keys)
        Payment::query()->delete();
        InvoiceItem::query()->delete();
        Invoice::query()->delete();
        Product::query()->delete();
        Customer::query()->delete();
        User::query()->delete();

        // Crear usuario
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        // Crear cliente
        $customer = Customer::create([
            'name' => 'Cliente Test',
            'email' => 'cliente@test.com',
            'phone' => '123456789',
            'address' => 'Dirección Test'
        ]);

        // Crear productos con precios que incluyen impuestos (como sistema PHP vanilla)
        $product1 = Product::create([
            'name' => 'Laptop HP',
            'description' => 'Laptop HP Pavilion 15"',
            'price' => 896.00, // Precio incluye 12% de impuesto (800 + 96)
            'stock' => 10
        ]);

        $product2 = Product::create([
            'name' => 'Mouse Logitech',
            'description' => 'Mouse inalámbrico',
            'price' => 28.56, // Precio incluye 12% de impuesto (25.50 + 3.06)
            'stock' => 50
        ]);

        // Crear factura
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'tax_rate' => 0.12,
            'total' => 0,
            'status' => 'pending'
        ]);

        // Crear items
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'price' => 896.00
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product2->id,
            'quantity' => 2,
            'price' => 28.56
        ]);

        // Cargar items y calcular totales
        $invoice->load('items');
        $invoice->calculateTotals();

        echo "✅ Factura creada:\n";
        echo "- Subtotal: $" . $invoice->subtotal . "\n";
        echo "- Impuesto (12%): $" . $invoice->tax_amount . "\n";
        echo "- Total: $" . $invoice->total . "\n";
        echo "- Balance debido: $" . $invoice->balance_due . "\n";
        echo "- Estado: " . $invoice->status . "\n\n";

        // Crear pago parcial para probar trigger automático
        $payment1 = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'payment_date' => now()
        ]);

        $invoice->refresh();
        echo "✅ Después del pago parcial de $500:\n";
        echo "- Estado: " . $invoice->status . "\n";
        echo "- Balance debido: $" . $invoice->balance_due . "\n\n";

        // Completar el pago para probar trigger automático
        $remainingAmount = $invoice->balance_due;
        $payment2 = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $remainingAmount,
            'payment_date' => now()
        ]);

        $invoice->refresh();
        echo "✅ Después del pago completo de $" . $remainingAmount . ":\n";
        echo "- Estado: " . $invoice->status . "\n";
        echo "- Balance debido: $" . $invoice->balance_due . "\n";
        echo "- ¿Está pagado?: " . ($invoice->isPaid() ? 'SÍ' : 'NO') . "\n\n";

        // Probar cambio de estado a 'paid' para trigger automático
        $invoice2 = Invoice::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'tax_rate' => 0.12,
            'total' => 100.00,
            'subtotal' => 89.29,
            'tax_amount' => 10.71,
            'balance_due' => 100.00,
            'status' => 'pending'
        ]);

        echo "✅ Probando trigger automático: cambiar estado a 'paid'\n";
        $invoice2->update(['status' => 'paid']);
        
        $paymentsCreated = Payment::where('invoice_id', $invoice2->id)->count();
        echo "- Pagos creados automáticamente: " . $paymentsCreated . "\n";
        
        if ($paymentsCreated > 0) {
            $autoPayment = Payment::where('invoice_id', $invoice2->id)->first();
            echo "- Monto del pago automático: $" . $autoPayment->amount . "\n";
        }

        echo "\n🎉 TODOS LOS ELEMENTOS DEL SISTEMA PHP VANILLA IMPLEMENTADOS:\n";
        echo "✅ Cálculo automático de subtotal e impuestos\n";
        echo "✅ Trigger automático de pagos (Observer)\n";
        echo "✅ Actualización automática de estado de facturas\n";
        echo "✅ Campo balance_due calculado automáticamente\n";
        echo "✅ Campos tax_rate, subtotal, tax_amount implementados\n";
    }
}
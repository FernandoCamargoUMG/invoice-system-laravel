<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Calcular totales de una factura
     */
    public function calculateInvoiceTotals(Invoice $invoice): array
    {
        $subtotal = $invoice->items->sum('total_price');
        $taxRate = config('app.tax_rate', 0.12);
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2)
        ];
    }

    /**
     * Crear factura con items
     */
    public function createInvoiceWithItems(array $invoiceData, array $items): Invoice
    {
        return DB::transaction(function () use ($invoiceData, $items) {
            
            // Crear la factura
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'customer_id' => $invoiceData['customer_id'],
                'user_id' => $invoiceData['user_id'],
                'invoice_date' => $invoiceData['invoice_date'],
                'due_date' => $invoiceData['due_date'] ?? null,
                'notes' => $invoiceData['notes'] ?? null,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'status' => 'draft'
            ]);

            // Procesar items
            foreach ($items as $itemData) {
                $this->addItemToInvoice($invoice, $itemData);
            }

            // Recalcular totales
            $totals = $this->calculateInvoiceTotals($invoice);
            $invoice->update($totals);

            return $invoice->load(['customer', 'items.product']);
        });
    }

    /**
     * Agregar item a la factura
     */
    public function addItemToInvoice(Invoice $invoice, array $itemData): InvoiceItem
    {
        $product = Product::findOrFail($itemData['product_id']);
        
        // Verificar stock
        if ($product->stock < $itemData['quantity']) {
            throw new \Exception("Stock insuficiente para el producto: {$product->name}");
        }

        // Crear el item
        $invoiceItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $itemData['product_id'],
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price']
        ]);

        // Crear movimiento de inventario y actualizar stock
        InventoryMovement::createMovement(
            $itemData['product_id'],
            'sale',
            $itemData['quantity'],
            $invoice->id,
            'sale',
            "Agregado a factura #{$invoice->id}",
            $invoice->user_id
        );

        return $invoiceItem;
    }

    /**
     * Eliminar factura y restaurar stock
     */
    public function deleteInvoiceAndRestoreStock(Invoice $invoice): void
    {
        if ($invoice->status !== 'draft') {
            throw new \Exception('Solo se pueden eliminar facturas en estado borrador');
        }

        DB::transaction(function () use ($invoice) {
            // Restaurar stock
            foreach ($invoice->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            $invoice->delete();
        });
    }

    /**
     * Cambiar estado de la factura
     */
    public function changeInvoiceStatus(Invoice $invoice, string $newStatus): Invoice
    {
        $validStatuses = ['draft', 'sent', 'paid', 'cancelled'];
        
        if (!in_array($newStatus, $validStatuses)) {
            throw new \Exception('Estado inválido');
        }

        $invoice->update(['status' => $newStatus]);
        
        return $invoice;
    }

    /**
     * Obtener estadísticas de facturas
     */
    public function getInvoiceStatistics(): array
    {
        return [
            'total_invoices' => Invoice::count(),
            'draft_invoices' => Invoice::where('status', 'draft')->count(),
            'sent_invoices' => Invoice::where('status', 'sent')->count(),
            'paid_invoices' => Invoice::where('status', 'paid')->count(),
            'cancelled_invoices' => Invoice::where('status', 'cancelled')->count(),
            'total_revenue' => Invoice::where('status', 'paid')->sum('total'),
            'pending_revenue' => Invoice::where('status', 'sent')->sum('total'),
        ];
    }
}
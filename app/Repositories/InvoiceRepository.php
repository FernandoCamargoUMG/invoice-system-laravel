<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

interface InvoiceRepositoryInterface
{
    public function findWithRelations(int $id): ?Invoice;
    public function findByCustomer(int $customerId): Collection;
    public function findByStatus(string $status): Collection;
    public function findByDateRange(string $startDate, string $endDate): Collection;
    public function createWithItems(array $data): Invoice;
    public function updateTotals(Invoice $invoice): Invoice;
}

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function findWithRelations(int $id): ?Invoice
    {
        return Invoice::with(['customer', 'items.product', 'payments'])->find($id);
    }

    public function findByCustomer(int $customerId): Collection
    {
        return Invoice::with(['items.product', 'payments'])
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByStatus(string $status): Collection
    {
        return Invoice::with(['customer', 'items.product'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByDateRange(string $startDate, string $endDate): Collection
    {
        return Invoice::with(['customer', 'items.product', 'payments'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createWithItems(array $data): Invoice
    {
        $invoice = Invoice::create([
            'customer_id' => $data['customer_id'],
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'status' => 'pending'
        ]);

        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $invoice->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);
            $subtotal += $item['quantity'] * $item['price'];
        }

        return $this->updateTotals($invoice);
    }

    public function updateTotals(Invoice $invoice): Invoice
    {
        $subtotal = $invoice->items()->sum(DB::raw('quantity * price'));
        $taxRate = config('app.tax_rate', 0.12);
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total
        ]);

        return $invoice->fresh();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'quote_number',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'total',
        'status',
        'quote_date',
        'valid_until',
        'notes',
        'converted_invoice_id'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'total' => 'decimal:2',
        'quote_date' => 'date',
        'valid_until' => 'date',
        'created_at' => 'datetime',
    ];


        // Los timestamps están habilitados por defecto

    /**
     * Relación con cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relación con usuario que creó la cotización
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con items de la cotización
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Relación con factura convertida (si existe)
     */
    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    /**
     * Scope para cotizaciones por estado
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para cotizaciones vencidas
     */
    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now()->toDateString())
                    ->whereIn('status', ['draft', 'sent']);
    }

    /**
     * Generar número de cotización correlativo
     */
    public static function generateQuoteNumber(): string
    {
        $lastQuote = self::latest('id')->first();
        $number = $lastQuote ? $lastQuote->id + 1 : 1;
        return 'QUO-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar si está vencida
     */
    public function isExpired(): bool
    {
        return $this->valid_until < now()->toDateString() && 
               in_array($this->status, ['draft', 'sent']);
    }

    /**
     * Verificar si puede ser convertida a factura
     */
    public function canBeConverted(): bool
    {
        return $this->status === 'approved' && !$this->converted_invoice_id;
    }

    /**
     * Calcular totales de la cotización (igual que facturas)
     */
    public function calculateTotals()
    {
        $taxRate = $this->tax_rate ?: config('app.tax_rate', 0.12);
        $subtotal = 0;
        $tax = 0;

        foreach ($this->items as $item) {
            // Calcular como en sistema original: precio incluye impuesto
            $itemSubtotal = $item->price / (1 + $taxRate);
            $itemTax = $item->price - $itemSubtotal;
            $subtotal += $itemSubtotal * $item->quantity;
            $tax += $itemTax * $item->quantity;
        }

        $total = $subtotal + $tax;

        $this->update([
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($tax, 2),
            'total' => round($total, 2),
            'tax_rate' => $taxRate
        ]);
    }

    /**
     * Convertir cotización a factura
     */
    public function convertToInvoice(): Invoice
    {
        if (!$this->canBeConverted()) {
            throw new \Exception('La cotización no puede ser convertida a factura');
        }

        // Crear factura con los mismos datos
        $invoice = Invoice::create([
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'tax_rate' => $this->tax_rate,
            'total' => $this->total,
            'balance_due' => $this->total,
            'status' => 'pending'
        ]);

        // Copiar items
        foreach ($this->items as $quoteItem) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $quoteItem->product_id,
                'quantity' => $quoteItem->quantity,
                'price' => $quoteItem->price
            ]);

            // Crear movimiento de inventario y reducir stock si es producto físico
            if ($quoteItem->product->isProduct()) {
                InventoryMovement::createMovement(
                    $quoteItem->product_id,
                    'sale',
                    $quoteItem->quantity,
                    $invoice->id,
                    'sale',
                    "Conversión de cotización #{$this->id} a factura #{$invoice->id}",
                    $this->user_id
                );
            }
        }

        // Marcar cotización como convertida
        $this->update([
            'status' => 'converted',
            'converted_invoice_id' => $invoice->id
        ]);

        return $invoice->load(['customer', 'user', 'items.product']);
    }
}
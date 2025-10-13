<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'invoice_date',
        'total',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'balance_due',
        'status'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'balance_due' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Relación con cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relación con usuario que creó la factura
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con items de la factura
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Scope para facturas por estado
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Calcular totales de la factura
     */
    public function calculateTotals()
    {
        $taxRate = $this->tax_rate ?: config('app.tax_rate', 0.12);
        $subtotal = 0;
        $tax = 0;

        foreach ($this->items as $item) {
            // Si el precio incluye impuesto, calcular el subtotal y el impuesto correctamente
            $itemSubtotal = $item->price / (1 + $taxRate);
            $itemTax = $item->price - $itemSubtotal;
            $subtotal += $itemSubtotal * $item->quantity;
            $tax += $itemTax * $item->quantity;
        }

        $total = $subtotal + $tax;
        $balanceDue = $total - $this->payments->sum('amount');

        $this->update([
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($tax, 2),
            'total' => round($total, 2),
            'tax_rate' => $taxRate,
            'balance_due' => round($balanceDue, 2)
        ]);
    }

    /**
     * Relación con pagos
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calcular saldo pendiente
     */
    public function getBalanceDueAttribute()
    {
        return $this->total - $this->payments->sum('amount');
    }

    /**
     * Verificar si está completamente pagada
     */
    public function isPaid(): bool
    {
        return $this->balance_due <= 0;
    }

    /**
     * Verificar si tiene pago parcial
     */
    public function isPartiallyPaid(): bool
    {
        $totalPaid = $this->payments->sum('amount');
        return $totalPaid > 0 && $totalPaid < $this->total;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'purchase_number',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'total',
        'status',
        'purchase_date',
        'notes'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'total' => 'decimal:2',
        'purchase_date' => 'date',
        'created_at' => 'datetime',
    ];

    // Los timestamps están habilitados por defecto

    /**
     * Relación con proveedor
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relación con usuario que creó la compra
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con items de la compra
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Scope para compras por estado
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Generar número de compra correlativo
     */
    public static function generatePurchaseNumber(): string
    {
        $lastPurchase = self::latest('id')->first();
        $number = $lastPurchase ? $lastPurchase->id + 1 : 1;
        return 'PUR-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular totales de la compra
     */
    public function calculateTotals()
    {
        $taxRate = $this->tax_rate ?: config('app.tax_rate', 0.12);
        $subtotal = 0;
        $tax = 0;

        foreach ($this->items as $item) {
            // Calcular como en sistema original: precio incluye impuesto
            $itemSubtotal = $item->cost_price / (1 + $taxRate);
            $itemTax = $item->cost_price - $itemSubtotal;
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
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'cost_price',
        'total_cost'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public $timestamps = false;

    /**
     * Relación con compra
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Relación con producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calcular total automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->total_cost = $item->quantity * $item->cost_price;
        });

        static::updating(function ($item) {
            $item->total_cost = $item->quantity * $item->cost_price;
        });
    }
}
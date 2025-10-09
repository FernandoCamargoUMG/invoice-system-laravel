<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'product_id',
        'quantity',
        'price',
        'total_price'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public $timestamps = false;

    /**
     * Relación con cotización
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
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
            $item->total_price = $item->quantity * $item->price;
        });

        static::updating(function ($item) {
            $item->total_price = $item->quantity * $item->price;
        });
    }
}
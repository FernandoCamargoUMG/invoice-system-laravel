<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'sku',
        'description',
        'price',
        'cost_price',
        'stock'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Relación con items de factura
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Relación con compras
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Relación con cotizaciones
     */
    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Relación con movimientos de inventario
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Scope para productos solamente
     */
    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }

    /**
     * Scope para servicios solamente
     */
    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    /**
     * Verificar si es un producto físico
     */
    public function isProduct(): bool
    {
        return $this->type === 'product';
    }

    /**
     * Verificar si es un servicio
     */
    public function isService(): bool
    {
        return $this->type === 'service';
    }

    /**
     * Scope para buscar productos
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
    }
}
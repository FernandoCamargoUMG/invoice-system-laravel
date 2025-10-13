<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Relación con producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crear un movimiento de inventario y actualizar el stock del producto.
     *
     * @param Product $product Producto afectado
     * @param string $type Tipo de movimiento ('sale', 'purchase', etc.)
     * @param int $quantity Cantidad movida
     * @param string|null $referenceType Tipo de referencia (ej: 'sale', 'purchase')
     * @param int|null $referenceId ID de la referencia (ej: id de factura)
     * @param string|null $notes Notas adicionales
     * @param int|null $userId Usuario que realiza el movimiento
     * @return self
     */
    public static function createMovement(
        Product $product,
        string $type,
        int $quantity,
        string $referenceType = null,
        int $referenceId = null,
        string $notes = null,
        int $userId = null
    ): self {
        $stockBefore = $product->stock;
        
    // Actualizar el stock del producto según el tipo de movimiento
        if ($type === 'purchase' || $type === 'return') {
            $product->increment('stock', $quantity);
        } elseif ($type === 'sale') {
            $product->decrement('stock', $quantity);
            $quantity = -$quantity; // Salidas restan stock
        }
        
        $stockAfter = $product->fresh()->stock;

        return self::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'user_id' => $userId ?: (\Illuminate\Support\Facades\Auth::id() ?? 1)
        ]);
    }

    /**
     * Scope para movimientos por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para movimientos por producto
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
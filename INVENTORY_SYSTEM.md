# Sistema de Inventario - ERP

## Cómo se insertan las transacciones al inventario

El sistema ERP maneja automáticamente todas las transacciones de inventario a través del modelo `InventoryMovement` y el método estático `createMovement()`.

## Método Principal: InventoryMovement::createMovement()

```php
InventoryMovement::createMovement(
    $productId,      // ID del producto
    $type,           // Tipo: 'purchase', 'sale', 'return', 'adjustment'
    $quantity,       // Cantidad (siempre positiva)
    $referenceId,    // ID de la referencia (factura, compra, etc.)
    $referenceType,  // Tipo de referencia: 'sale', 'purchase', 'return'
    $notes,          // Notas descriptivas
    $userId          // ID del usuario (opcional)
);
```

## Tipos de Movimientos Automáticos

### 1. Compras (Entradas de Stock)
**Ubicación:** `PurchaseController@receive()`
```php
InventoryMovement::createMovement(
    $item['product_id'],
    'purchase',
    $item['quantity_received'],
    $purchase->id,
    'purchase',
    "Recepción de compra #{$purchase->id}",
    $request->user()->id
);
```

### 2. Ventas por Factura (Salidas de Stock)
**Ubicación:** `InvoiceController@store()`
```php
InventoryMovement::createMovement(
    $itemData['product_id'],
    'sale',
    $itemData['quantity'],
    $invoice->id,
    'sale',
    "Venta - Factura #{$invoice->id}",
    $invoice->user_id
);
```

### 3. Conversión de Cotización a Factura
**Ubicación:** `Quote@convertToInvoice()`
```php
InventoryMovement::createMovement(
    $quoteItem->product_id,
    'sale',
    $quoteItem->quantity,
    $invoice->id,
    'sale',
    "Conversión de cotización #{$this->id} a factura #{$invoice->id}",
    $this->user_id
);
```

### 4. Agregado de Items a Facturas
**Ubicación:** `InvoiceService@addItemToInvoice()`
```php
InventoryMovement::createMovement(
    $itemData['product_id'],
    'sale',
    $itemData['quantity'],
    $invoice->id,
    'sale',
    "Agregado a factura #{$invoice->id}",
    $invoice->user_id
);
```

## Funcionamiento Interno

### 1. Captura del Stock Actual
```php
$product = Product::findOrFail($productId);
$stockBefore = $product->stock;
```

### 2. Actualización Automática del Stock
```php
if ($type === 'purchase' || $type === 'return') {
    $product->increment('stock', $quantity);  // Entrada
} elseif ($type === 'sale') {
    $product->decrement('stock', $quantity);  // Salida
    $quantity = -$quantity; // Negativo para salidas
}
```

### 3. Registro del Movimiento
```php
return self::create([
    'product_id' => $product->id,
    'type' => $type,
    'quantity' => $quantity, // Positivo para entradas, negativo para salidas
    'stock_before' => $stockBefore,
    'stock_after' => $stockAfter,
    'reference_type' => $referenceType,
    'reference_id' => $referenceId,
    'notes' => $notes,
    'user_id' => $userId
]);
```

## Estructura de la Tabla inventory_movements

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único del movimiento |
| product_id | bigint | FK al producto |
| type | enum | purchase, sale, return, adjustment |
| quantity | integer | Cantidad (+ entrada, - salida) |
| stock_before | integer | Stock antes del movimiento |
| stock_after | integer | Stock después del movimiento |
| reference_type | string | Tipo de referencia (sale, purchase) |
| reference_id | bigint | ID de la referencia |
| notes | text | Notas del movimiento |
| user_id | bigint | Usuario que realizó el movimiento |
| created_at | timestamp | Fecha de creación |

## Relaciones del Modelo

```php
// Producto relacionado
public function product(): BelongsTo
{
    return $this->belongsTo(Product::class);
}

// Usuario que realizó el movimiento
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

## Observadores Automáticos

El sistema también incluye observadores que se activan automáticamente:

### InvoiceObserver
- Se ejecuta cuando se crea una factura
- Puede disparar eventos adicionales de inventario

### PaymentObserver
- Se ejecuta en transacciones de pago
- Puede afectar el estado de facturas y por ende el inventario

## Consultas de Inventario

### Obtener todos los movimientos de un producto
```php
$movements = InventoryMovement::where('product_id', $productId)
    ->with(['product', 'user'])
    ->orderBy('created_at', 'desc')
    ->get();
```

### Stock actual vs histórico
```php
// Stock actual
$currentStock = Product::find($productId)->stock;

// Último movimiento registrado
$lastMovement = InventoryMovement::where('product_id', $productId)
    ->latest()
    ->first();
    
$lastRecordedStock = $lastMovement->stock_after ?? 0;
```

## Validaciones Automáticas

### Verificación de Stock antes de Ventas
```php
// En InvoiceController y InvoiceService
if ($product->stock < $itemData['quantity']) {
    throw new \Exception("Stock insuficiente para el producto: {$product->name}");
}
```

### Integridad Referencial
- Todas las transacciones mantienen referencia al documento origen
- Los movimientos son inmutables una vez creados
- El stock se actualiza automáticamente y de forma atómica

## Endpoints de API Relacionados

### Listar movimientos de inventario
```
GET /api/inventory-movements
GET /api/inventory-movements?product_id=1
```

### Crear movimiento manual (ajuste)
```
POST /api/inventory-movements
{
  "product_id": 1,
  "type": "adjustment",
  "quantity": 10,
  "notes": "Ajuste por inventario físico"
}
```

## Resumen

El sistema maneja **automáticamente** todas las transacciones de inventario:

1. **Compras** → Incrementan stock y crean movimiento de entrada
2. **Ventas** → Decrementan stock y crean movimiento de salida  
3. **Conversiones** → De cotización a factura con movimiento automático
4. **Ajustes** → Movimientos manuales para correcciones

Cada movimiento mantiene un audit trail completo con:
- Stock antes y después del movimiento
- Referencia al documento origen
- Usuario responsable
- Notas descriptivas
- Timestamp exacto

El sistema garantiza la integridad del inventario y proporciona trazabilidad completa de todos los movimientos de stock.
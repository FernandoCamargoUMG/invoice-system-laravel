# TEST DE PURCHASES API

Para probar si funciona el endpoint de purchases, puedes usar este JSON en Postman o en tu frontend:

## 1. Primero obtén un token JWT

### POST /api/auth/login
```json
{
  "email": "admin@gmail.com",
  "password": "12345678"
}
```

## 2. Luego crea una compra

### POST /api/purchases
**Headers:**
```
Authorization: Bearer {tu_token_jwt}
Content-Type: application/json
Accept: application/json
```

**Body (raw JSON):**
```json
{
  "supplier_id": 1,
  "purchase_date": "2025-10-13",
  "notes": "Compra de prueba desde API",
  "items": [
    {
      "product_id": 1,
      "quantity": 20,
      "cost_price": 15.50
    },
    {
      "product_id": 2,
      "quantity": 10,
      "cost_price": 25.75
    }
  ]
}
```

## 3. Verificar que se creó correctamente

### GET /api/purchases
**Headers:** Mismo Authorization

Debería devolver la compra creada con status "pending".

## 4. Para recibir mercancía (actualizar inventario)

### POST /api/purchases/{id}/receive
**Headers:** Mismo Authorization

Esto cambiará el status a "received" y creará movimientos de inventario automáticamente.

## ERRORES COMUNES RESUELTOS:

✅ **timestamps**: Ahora el modelo maneja created_at y updated_at correctamente
✅ **InventoryMovement**: Corregido el método createMovement con parámetros correctos  
✅ **Migración**: Agregado timestamps() en lugar de solo created_at
✅ **Seeders**: Corregidos los índices de customers para evitar errores

## ESTRUCTURA DE BASE DE DATOS PURCHASES:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | Primary Key |
| supplier_id | bigint | FK a suppliers |
| user_id | bigint | FK a users |
| purchase_number | string | Número único (PUR-000001) |
| subtotal | decimal(10,2) | Subtotal sin impuestos |
| tax_amount | decimal(10,2) | Monto de impuestos |
| tax_rate | decimal(5,4) | Tasa de impuesto (0.12) |
| total | decimal(10,2) | Total con impuestos |
| status | enum | pending, received, canceled |
| purchase_date | date | Fecha de la compra |
| notes | text | Observaciones (opcional) |
| created_at | timestamp | Fecha creación |
| updated_at | timestamp | Fecha actualización |

## RELACIONES:
- `supplier()` → BelongsTo Supplier
- `user()` → BelongsTo User  
- `items()` → HasMany PurchaseItem

Cada PurchaseItem tiene:
- product_id, quantity, cost_price, total_cost
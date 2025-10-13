# API PURCHASES - Campos para Frontend

## Endpoint POST /api/purchases

### Campos REQUERIDOS que debe enviar el frontend:

```json
{
  "supplier_id": 1,                    // OBLIGATORIO - ID del proveedor (debe existir en suppliers)
  "purchase_date": "2025-10-13",       // OBLIGATORIO - Fecha de la compra (formato YYYY-MM-DD)
  "items": [                           // OBLIGATORIO - Array de productos, mínimo 1 item
    {
      "product_id": 1,                 // OBLIGATORIO - ID del producto (debe existir en products)
      "quantity": 10,                  // OBLIGATORIO - Cantidad entera, mínimo 1
      "cost_price": 25.50              // OBLIGATORIO - Precio de costo, mínimo 0.01
    }
  ]
}
```

### Campos OPCIONALES:

```json
{
  "notes": "Observaciones sobre la compra"  // OPCIONAL - Notas adicionales
}
```

### Campos que se generan AUTOMÁTICAMENTE:

- `user_id`: Se toma del usuario autenticado
- `purchase_number`: Se genera automáticamente (PUR-000001, PUR-000002, etc.)
- `status`: Se inicia como 'pending'
- `subtotal`: Se calcula automáticamente
- `tax_amount`: Se calcula automáticamente (12% IVA)
- `tax_rate`: Se asigna automáticamente (0.12)
- `total`: Se calcula automáticamente
- `created_at`: Timestamp automático
- `updated_at`: Timestamp automático

### Ejemplo COMPLETO de request desde el frontend:

```json
{
  "supplier_id": 2,
  "purchase_date": "2025-10-13",
  "notes": "Compra de productos para inventario inicial",
  "items": [
    {
      "product_id": 1,
      "quantity": 50,
      "cost_price": 15.75
    },
    {
      "product_id": 3,
      "quantity": 25,
      "cost_price": 32.50
    }
  ]
}
```

### Validaciones que se ejecutan:

1. **supplier_id**: Debe existir en la tabla suppliers
2. **purchase_date**: Debe ser una fecha válida
3. **items**: No puede estar vacío, debe tener al menos 1 item
4. **product_id**: Cada producto debe existir en la tabla products
5. **quantity**: Debe ser un número entero mayor a 0
6. **cost_price**: Debe ser un número decimal mayor a 0.01

### Respuesta de éxito (201):

```json
{
  "success": true,
  "message": "Compra creada exitosamente",
  "data": {
    "id": 1,
    "supplier_id": 2,
    "user_id": 1,
    "purchase_number": "PUR-000001",
    "subtotal": 1600.00,
    "tax_amount": 192.00,
    "tax_rate": 0.12,
    "total": 1792.00,
    "status": "pending",
    "purchase_date": "2025-10-13",
    "notes": "Compra de productos para inventario inicial",
    "created_at": "2025-10-13T10:30:00.000000Z",
    "updated_at": "2025-10-13T10:30:00.000000Z",
    "supplier": {
      "id": 2,
      "name": "Proveedor ABC"
    },
    "user": {
      "id": 1,
      "name": "Usuario Admin"
    },
    "items": [
      {
        "id": 1,
        "purchase_id": 1,
        "product_id": 1,
        "quantity": 50,
        "cost_price": 15.75,
        "total_cost": 787.50,
        "product": {
          "id": 1,
          "name": "Producto A"
        }
      }
    ]
  }
}
```

### Respuesta de error (422) - Validación:

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "supplier_id": ["El campo supplier id es obligatorio."],
    "items.0.quantity": ["La cantidad debe ser mayor a 0."],
    "items.1.product_id": ["El producto seleccionado no existe."]
  }
}
```

### Headers necesarios:

```
Content-Type: application/json
Authorization: Bearer {token_jwt}
Accept: application/json
```

## ERRORES COMUNES Y SOLUCIONES:

### Error: "supplier_id is required"
- **Problema**: No se está enviando el supplier_id
- **Solución**: Incluir el campo supplier_id con un valor válido

### Error: "The selected supplier id is invalid"
- **Problema**: El supplier_id no existe en la base de datos
- **Solución**: Verificar que el proveedor exista antes de enviar

### Error: "items field is required"
- **Problema**: No se están enviando los items de la compra
- **Solución**: Incluir array de items con al menos 1 elemento

### Error: "The selected product id is invalid"
- **Problema**: Un product_id no existe en la base de datos
- **Solución**: Verificar que todos los productos existan

### Error: "quantity must be at least 1"
- **Problema**: La cantidad es 0 o negativa
- **Solución**: Usar cantidades positivas (1 o más)

### Error: "cost_price must be at least 0.01"
- **Problema**: El precio de costo es 0 o negativo
- **Solución**: Usar precios mayores a 0

## PRUEBA CON POSTMAN:

### URL: POST {{base_url}}/api/purchases

### Body (raw JSON):
```json
{
  "supplier_id": 1,
  "purchase_date": "2025-10-13",
  "notes": "Compra de prueba desde Postman",
  "items": [
    {
      "product_id": 1,
      "quantity": 10,
      "cost_price": 25.50
    },
    {
      "product_id": 2,
      "quantity": 5,
      "cost_price": 45.75
    }
  ]
}
```

### Headers:
```
Content-Type: application/json
Authorization: Bearer {{jwt_token}}
Accept: application/json
```
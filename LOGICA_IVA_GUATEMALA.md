# CÁLCULO DE IVA EN GUATEMALA - COMPRAS vs VENTAS

## 🇬🇹 LÓGICA FISCAL DE GUATEMALA

### **COMPRAS (Lo que implementamos):**
- El proveedor nos factura con **IVA INCLUIDO**
- `cost_price` = Precio CON IVA (Q112.00)
- Necesitamos **separar** el IVA para contabilidad

**Fórmula:**
```
Precio sin IVA = Precio con IVA / (1 + 0.12)
IVA = Precio con IVA - Precio sin IVA
Total = Precio con IVA
```

**Ejemplo:**
- `cost_price`: Q112.00 (precio con IVA que nos cobra el proveedor)
- `subtotal`: Q112.00 / 1.12 = Q100.00 (precio sin IVA)
- `tax_amount`: Q112.00 - Q100.00 = Q12.00 (IVA)
- `total`: Q112.00 (total que pagamos)

### **VENTAS (Para las facturas):**
- Nosotros cobramos al cliente con **IVA SEPARADO**
- `price` = Precio SIN IVA (Q100.00)
- Calculamos y **agregamos** el IVA

**Fórmula:**
```
IVA = Precio sin IVA * 0.12
Total = Precio sin IVA + IVA
```

**Ejemplo:**
- `price`: Q100.00 (precio sin IVA que cobramos)
- `tax_amount`: Q100.00 * 0.12 = Q12.00 (IVA)
- `total`: Q100.00 + Q12.00 = Q112.00 (total que cobra al cliente)

## 📊 EJEMPLO PRÁCTICO CON TU JSON:

### **Request de Compra:**
```json
{
  "supplier_id": 1,
  "purchase_date": "2025-10-13",
  "notes": "Compra de prueba desde Postman",
  "items": [
    {
      "product_id": 1,
      "quantity": 10,
      "cost_price": 25.50  // ← YA INCLUYE IVA (Q25.50)
    },
    {
      "product_id": 2,
      "quantity": 5,
      "cost_price": 45.75  // ← YA INCLUYE IVA (Q45.75)
    }
  ]
}
```

### **Cálculo automático en el sistema:**

**Item 1:**
- Cantidad: 10
- Precio unitario con IVA: Q25.50
- Total item con IVA: 10 × Q25.50 = Q255.00
- Precio sin IVA: Q255.00 ÷ 1.12 = Q227.68
- IVA del item: Q255.00 - Q227.68 = Q27.32

**Item 2:**
- Cantidad: 5  
- Precio unitario con IVA: Q45.75
- Total item con IVA: 5 × Q45.75 = Q228.75
- Precio sin IVA: Q228.75 ÷ 1.12 = Q204.24
- IVA del item: Q228.75 - Q204.24 = Q24.51

**Totales de la compra:**
- `subtotal`: Q227.68 + Q204.24 = Q431.92 (sin IVA)
- `tax_amount`: Q27.32 + Q24.51 = Q51.83 (IVA total)
- `total`: Q431.92 + Q51.83 = Q483.75 (total con IVA)

### **Response esperada:**
```json
{
  "success": true,
  "message": "Compra creada exitosamente",
  "data": {
    "id": 1,
    "supplier_id": 1,
    "user_id": 1,
    "purchase_number": "PUR-000001",
    "subtotal": 431.92,     // Precio sin IVA
    "tax_amount": 51.83,    // IVA total
    "tax_rate": 0.12,       // 12% IVA Guatemala
    "total": 483.75,        // Total con IVA
    "status": "pending",
    "purchase_date": "2025-10-13",
    "notes": "Compra de prueba desde Postman",
    // ... resto de datos
  }
}
```

## 🔄 DIFERENCIA CLAVE:

| Tipo | Precio Input | Cálculo | Resultado |
|------|-------------|---------|-----------|
| **Compra** | CON IVA | Separar IVA | `subtotal` + `tax_amount` = `total` |
| **Venta** | SIN IVA | Agregar IVA | `subtotal` + `tax_amount` = `total` |

## ✅ IMPLEMENTADO EN:

1. **PurchaseController@store()** - Cálculo correcto para Guatemala
2. **Purchase@calculateTotals()** - Ya estaba correcto
3. **InvoiceController@store()** - Maneja ventas (agregar IVA)

Ahora el sistema maneja correctamente la lógica fiscal guatemalteca para compras y ventas.
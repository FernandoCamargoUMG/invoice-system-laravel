# Sistema de Facturación - API Endpoints

## Base URL
```
http://127.0.0.1:8000/api
```

## Autenticación

### 1. Registro de Usuario
```http
POST /auth/register
Content-Type: application/json

{
    "name": "Nuevo Usuario",
    "email": "nuevo@email.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### 2. Login
```http
POST /auth/login
Content-Type: application/json

{
    "email": "admin@invoice.com",
    "password": "password"
}
```

**Respuesta:**
```json
{
    "message": "Login exitoso",
    "user": {
        "id": 1,
        "name": "Admin",
        "email": "admin@invoice.com",
        "role": "admin"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

### 3. Perfil del Usuario (requiere JWT)
```http
GET /auth/profile
Authorization: Bearer {token}
```

## Clientes

### 1. Listar Clientes
```http
GET /customers
Authorization: Bearer {token}
```

### 2. Crear Cliente
```http
POST /customers
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Cliente Nuevo",
    "email": "cliente@email.com",
    "phone": "123456789",
    "address": "Dirección del cliente"
}
```

### 3. Ver Cliente
```http
GET /customers/{id}
Authorization: Bearer {token}
```

### 4. Actualizar Cliente
```http
PUT /customers/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Cliente Actualizado",
    "email": "cliente_actualizado@email.com",
    "phone": "987654321",
    "address": "Nueva dirección"
}
```

## Productos

### 1. Listar Productos
```http
GET /products
Authorization: Bearer {token}
```

### 2. Crear Producto
```http
POST /products
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Producto Nuevo",
    "description": "Descripción del producto",
    "price": 99.99,
    "stock": 20
}
```

### 3. Actualizar Stock
```http
PATCH /products/{id}/stock
Authorization: Bearer {token}
Content-Type: application/json

{
    "stock": 50
}
```

## Facturas

### 1. Listar Facturas
```http
GET /invoices
Authorization: Bearer {token}
```

### 2. Crear Factura
```http
POST /invoices
Authorization: Bearer {token}
Content-Type: application/json

{
    "customer_id": 1,
    "items": [
        {
            "product_id": 1,
            "quantity": 2,
            "price": 799.99
        },
        {
            "product_id": 2,
            "quantity": 1,
            "price": 25.50
        }
    ]
}
```

### 3. Ver Factura
```http
GET /invoices/{id}
Authorization: Bearer {token}
```

### 4. Actualizar Estado de Factura
```http
PATCH /invoices/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": "paid"
}
```

**Estados válidos:** `pending`, `paid`, `canceled`

### 5. Eliminar Factura
```http
DELETE /invoices/{id}
Authorization: Bearer {token}
```

## Datos de Prueba Creados

### Usuarios:
- **Admin:** admin@invoice.com / password
- **Cajero:** cajero@invoice.com / password

### Clientes:
- Juan Pérez (juan@email.com)
- María García (maria@email.com)

### Productos:
- Laptop HP ($799.99, stock: 10)
- Mouse Logitech ($25.50, stock: 50)
- Teclado Mecánico ($89.99, stock: 25)

## Notas de Uso

1. **Autenticación:** Todas las rutas (excepto register/login) requieren el header `Authorization: Bearer {token}`
2. **JWT Token:** El token expira en 24 horas
3. **Base de Datos:** Los datos se mantienen entre ejecuciones
4. **Estados de Facturas:** 
   - `pending`: Factura pendiente de pago
   - `paid`: Factura pagada
   - `canceled`: Factura cancelada
5. **Stock:** Se actualiza automáticamente al crear facturas
# 📚 API Documentation - Sistema ERP Laravel

## 🎯 Overview

Esta es la documentación completa de la API RESTful del Sistema ERP desarrollado en Laravel. La API proporciona endpoints para gestionar proveedores, productos, clientes, compras, cotizaciones, facturas, pagos e inventario.

### Base URL
```
http://localhost:8000/api
```

### Versión
`v1.0.0`

### Formato de Respuesta
Todas las respuestas de la API siguen un formato JSON consistente:

```json
{
  "success": true,
  "message": "Descripción del resultado",
  "data": {},
  "errors": {} // Solo presente en errores de validación
}
```

---

## 🔐 Autenticación

### Tipo de Autenticación
La API utiliza **JWT (JSON Web Tokens)** para autenticación y autorización.

### Headers Requeridos
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {access_token}
```

### Duración de Tokens
- **Access Token**: 4 horas
- **Refresh Token**: 7 días

---

## 📝 Endpoints de Autenticación

### 1. Registro de Usuario
Crea una nueva cuenta de usuario en el sistema.

```http
POST /auth/register
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Juan Pérez",
  "email": "juan@ejemplo.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Validaciones:**
- `name`: Requerido, string, máximo 255 caracteres
- `email`: Requerido, email válido, único en el sistema
- `password`: Requerido, mínimo 8 caracteres
- `password_confirmation`: Requerido, debe coincidir con password

**Response 201 - Éxito:**
```json
{
  "message": "Usuario registrado exitosamente",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@ejemplo.com"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

### 2. Iniciar Sesión
Autentica un usuario y proporciona tokens de acceso.

```http
POST /auth/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "juan@ejemplo.com",
  "password": "password123"
}
```

**Response 200 - Éxito:**
```json
{
  "message": "Login exitoso",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@ejemplo.com"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_token": "def50200e3b0c44298fc1c149afbf4c8996fb924...",
  "token_type": "Bearer",
  "access_token_expires_in": 14400,
  "refresh_token_expires_in": 604800
}
```

### 3. Obtener Perfil de Usuario
Obtiene la información del usuario autenticado.

```http
GET /auth/profile
Authorization: Bearer {access_token}
```

**Response 200 - Éxito:**
```json
{
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@ejemplo.com"
  }
}
```

### 4. Renovar Token de Acceso
Genera un nuevo access token usando el refresh token.

```http
POST /auth/refresh
Content-Type: application/json
```

**Request Body:**
```json
{
  "refresh_token": "def50200e3b0c44298fc1c149afbf4c8996fb924..."
}
```

### 5. Cerrar Sesión
Invalida los tokens del usuario.

```http
POST /auth/logout
Authorization: Bearer {access_token}
```

---

## 🏢 Gestión de Proveedores

### 1. Listar Proveedores
Obtiene una lista paginada de proveedores con filtros opcionales.

```http
GET /suppliers
Authorization: Bearer {access_token}
```

**Query Parameters:**
- `search` (string, opcional): Busca por nombre, email o tax_id
- `active` (boolean, opcional): Filtra por estado activo/inactivo
- `per_page` (integer, opcional): Elementos por página (default: 15)

### 2. Crear Proveedor
Crea un nuevo proveedor en el sistema.

```http
POST /suppliers
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Proveedor XYZ S.A.",
  "email": "contacto@xyz.com",
  "phone": "555-0456",
  "address": "Calle Secundaria 456, Ciudad",
  "contact_person": "María García",
  "tax_id": "0987654321",
  "notes": "Proveedor de materiales especializados"
}
```

### 3. Obtener Proveedor Específico
```http
GET /suppliers/{id}
Authorization: Bearer {access_token}
```

### 4. Actualizar Proveedor
```http
PUT /suppliers/{id}
Authorization: Bearer {access_token}
```

### 5. Eliminar Proveedor
```http
DELETE /suppliers/{id}
Authorization: Bearer {access_token}
```

### 6. Activar/Desactivar Proveedor
```http
PATCH /suppliers/{id}/toggle-status
Authorization: Bearer {access_token}
```

### 7. Obtener Proveedores Activos
```http
GET /suppliers-active
Authorization: Bearer {access_token}
```

---

## 📦 Gestión de Productos

### 1. Listar Productos
```http
GET /products
Authorization: Bearer {access_token}
```

### 2. Crear Producto
```http
POST /products
Authorization: Bearer {access_token}
```

### 3. Obtener Producto Específico
```http
GET /products/{id}
Authorization: Bearer {access_token}
```

### 4. Actualizar Producto
```http
PUT /products/{id}
Authorization: Bearer {access_token}
```

### 5. Eliminar Producto
```http
DELETE /products/{id}
Authorization: Bearer {access_token}
```

### 6. Actualizar Stock
```http
PATCH /products/{id}/stock
Authorization: Bearer {access_token}
```

---

## 👥 Gestión de Clientes

### 1. Listar Clientes
```http
GET /customers
Authorization: Bearer {access_token}
```

### 2. Crear Cliente
```http
POST /customers
Authorization: Bearer {access_token}
```

### 3. Obtener Cliente Específico
```http
GET /customers/{id}
Authorization: Bearer {access_token}
```

### 4. Actualizar Cliente
```http
PUT /customers/{id}
Authorization: Bearer {access_token}
```

### 5. Eliminar Cliente
```http
DELETE /customers/{id}
Authorization: Bearer {access_token}
```

---

## 🛒 Gestión de Compras

### 1. Listar Compras
```http
GET /purchases
Authorization: Bearer {access_token}
```

### 2. Crear Compra
```http
POST /purchases
Authorization: Bearer {access_token}
```

### 3. Obtener Compra Específica
```http
GET /purchases/{id}
Authorization: Bearer {access_token}
```

### 4. Actualizar Compra
```http
PUT /purchases/{id}
Authorization: Bearer {access_token}
```

### 5. Eliminar Compra
```http
DELETE /purchases/{id}
Authorization: Bearer {access_token}
```

### 6. Recibir Mercancía
```http
PATCH /purchases/{id}/receive
Authorization: Bearer {access_token}
```

### 7. Cancelar Compra
```http
PATCH /purchases/{id}/cancel
Authorization: Bearer {access_token}
```

### 8. Estadísticas de Compras
```http
GET /purchases-stats
Authorization: Bearer {access_token}
```

---

## 💡 Gestión de Cotizaciones

### 1. Listar Cotizaciones
```http
GET /quotes
Authorization: Bearer {access_token}
```

### 2. Crear Cotización
```http
POST /quotes
Authorization: Bearer {access_token}
```

### 3. Obtener Cotización Específica
```http
GET /quotes/{id}
Authorization: Bearer {access_token}
```

### 4. Actualizar Cotización
```http
PUT /quotes/{id}
Authorization: Bearer {access_token}
```

### 5. Eliminar Cotización
```http
DELETE /quotes/{id}
Authorization: Bearer {access_token}
```

### 6. Enviar Cotización
```http
PATCH /quotes/{id}/send
Authorization: Bearer {access_token}
```

### 7. Aprobar Cotización
```http
PATCH /quotes/{id}/approve
Authorization: Bearer {access_token}
```

### 8. Rechazar Cotización
```http
PATCH /quotes/{id}/reject
Authorization: Bearer {access_token}
```

### 9. Convertir Cotización a Factura
```http
POST /quotes/{id}/convert-to-invoice
Authorization: Bearer {access_token}
```

### 10. Marcar Cotizaciones Vencidas
```http
PATCH /quotes/mark-expired
Authorization: Bearer {access_token}
```

### 11. Estadísticas de Cotizaciones
```http
GET /quotes-stats
Authorization: Bearer {access_token}
```

---

## 📄 Gestión de Facturas

### 1. Listar Facturas
```http
GET /invoices
Authorization: Bearer {access_token}
```

### 2. Crear Factura
```http
POST /invoices
Authorization: Bearer {access_token}
```

### 3. Obtener Factura Específica
```http
GET /invoices/{id}
Authorization: Bearer {access_token}
```

### 4. Actualizar Factura
```http
PUT /invoices/{id}
Authorization: Bearer {access_token}
```

### 5. Eliminar Factura
```http
DELETE /invoices/{id}
Authorization: Bearer {access_token}
```

### 6. Actualizar Estado de Factura
```http
PATCH /invoices/{id}/status
Authorization: Bearer {access_token}
```

---

## 💳 Gestión de Pagos

### 1. Listar Pagos
```http
GET /payments
Authorization: Bearer {access_token}
```

### 2. Registrar Pago
```http
POST /payments
Authorization: Bearer {access_token}
```

### 3. Obtener Pago Específico
```http
GET /payments/{id}
Authorization: Bearer {access_token}
```

### 4. Actualizar Pago
```http
PUT /payments/{id}
Authorization: Bearer {access_token}
```

### 5. Eliminar Pago
```http
DELETE /payments/{id}
Authorization: Bearer {access_token}
```

### 6. Obtener Pagos de una Factura
```http
GET /invoices/{invoice_id}/payments
Authorization: Bearer {access_token}
```

---

## 📊 Gestión de Inventario

### 1. Listar Movimientos de Inventario
```http
GET /inventory-movements
Authorization: Bearer {access_token}
```

### 2. Crear Ajuste de Inventario
```http
POST /inventory-adjustments
Authorization: Bearer {access_token}
```

### 3. Obtener Movimientos por Producto
```http
GET /inventory-movements/product/{product_id}
Authorization: Bearer {access_token}
```

### 4. Resumen de Inventario
```http
GET /inventory-summary
Authorization: Bearer {access_token}
```

### 5. Estadísticas de Movimientos
```http
GET /inventory-stats
Authorization: Bearer {access_token}
```

### 6. Alertas de Inventario
```http
GET /inventory-alerts
Authorization: Bearer {access_token}
```

### 7. Exportar Inventario
```http
GET /inventory-export
Authorization: Bearer {access_token}
```

---

## 👤 Gestión de Usuarios

### 1. Listar Usuarios
```http
GET /users
Authorization: Bearer {access_token}
```

### 2. Crear Usuario
```http
POST /users
Authorization: Bearer {access_token}
```

### 3. Obtener Usuario Específico
```http
GET /users/{id}
Authorization: Bearer {access_token}
```

### 4. Actualizar Usuario
```http
PUT /users/{id}
Authorization: Bearer {access_token}
```

### 5. Eliminar Usuario
```http
DELETE /users/{id}
Authorization: Bearer {access_token}
```

### 6. Cambiar Rol de Usuario
```http
PATCH /users/{id}/role
Authorization: Bearer {access_token}
```

---

## 🔧 Códigos de Estado HTTP

| Código | Nombre | Descripción |
|--------|--------|-------------|
| 200 | OK | Solicitud exitosa |
| 201 | Created | Recurso creado exitosamente |
| 400 | Bad Request | Solicitud malformada |
| 401 | Unauthorized | Token de autenticación inválido o faltante |
| 403 | Forbidden | Acceso denegado |
| 404 | Not Found | Recurso no encontrado |
| 422 | Unprocessable Entity | Error de validación de datos |
| 500 | Internal Server Error | Error interno del servidor |

---

## 🚨 Manejo de Errores

### Error de Autenticación (401)
```json
{
  "success": false,
  "message": "Token no válido o expirado"
}
```

### Error de Validación (422)
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "email": ["El campo email ya está en uso"],
    "name": ["El campo name es obligatorio"],
    "price": ["El campo price debe ser mayor a 0"]
  }
}
```

### Recurso No Encontrado (404)
```json
{
  "success": false,
  "message": "Recurso no encontrado"
}
```

### Error del Servidor (500)
```json
{
  "success": false,
  "message": "Error interno del servidor",
  "error": "Descripción técnica del error"
}
```

---

*📚 Documentación API - Sistema ERP Laravel v1.0.0*  
*📅 Última actualización: 3 de noviembre de 2025*
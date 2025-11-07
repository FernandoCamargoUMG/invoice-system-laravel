# 🔐 Sistema de Roles y Autorización - Guía Completa

## 📋 Roles Definidos en el Sistema

Tu sistema maneja dos roles principales:
- **`admin`**: Administrador con acceso completo
- **`cashier`**: Cajero con acceso limitado

## 🛠️ Cómo Usar el Middleware de Roles

### 1. En Rutas (routes/api.php)

```php
// Solo administradores pueden gestionar usuarios
Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/role', [UserController::class, 'changeRole']);
});

// Solo administradores pueden gestionar proveedores
Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::apiResource('suppliers', SupplierController::class);
    Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus']);
});

// Administradores y cajeros pueden crear facturas
Route::middleware(['jwt', 'role:admin,cashier'])->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('payments', PaymentController::class);
});

// Solo lectura para cajeros en algunos recursos
Route::middleware(['jwt', 'role:cashier'])->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('customers', [CustomerController::class, 'index']);
});
```

### 2. En Controladores (Validación Adicional)

```php
// En cualquier controlador
public function destroy(Request $request, $id)
{
    $user = $request->user();
    
    // Solo admin puede eliminar
    if ($user->role !== 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'Solo los administradores pueden eliminar registros'
        ], 403);
    }
    
    // Lógica de eliminación...
}

// Método para verificar múltiples roles
private function hasRole($user, $roles)
{
    return in_array($user->role, is_array($roles) ? $roles : [$roles]);
}
```

### 3. En el Modelo User (Métodos Helper)

```php
// Agregar estos métodos al modelo User
public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function isCashier(): bool
{
    return $this->role === 'cashier';
}

public function hasRole(string|array $roles): bool
{
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array($this->role, $roles);
}

public function can(string $permission): bool
{
    // Definir permisos por rol
    $permissions = [
        'admin' => [
            'users.create', 'users.update', 'users.delete',
            'suppliers.create', 'suppliers.update', 'suppliers.delete',
            'products.create', 'products.update', 'products.delete',
            'invoices.create', 'invoices.update', 'invoices.delete',
            'reports.view', 'settings.manage'
        ],
        'cashier' => [
            'invoices.create', 'invoices.update',
            'payments.create', 'payments.update',
            'customers.create', 'customers.update',
            'products.view'
        ]
    ];
    
    return isset($permissions[$this->role]) && 
           in_array($permission, $permissions[$this->role]);
}
```

## 🎯 Ejemplos Prácticos de Restricciones

### Escenario 1: Solo Admins pueden gestionar productos

```php
// routes/api.php
Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
});

// Cajeros solo pueden ver productos
Route::middleware(['jwt', 'role:cashier'])->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
});
```

### Escenario 2: Ambos roles pueden crear facturas, solo admin puede eliminar

```php
// routes/api.php
Route::middleware(['jwt', 'role:admin,cashier'])->group(function () {
    Route::post('invoices', [InvoiceController::class, 'store']);
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update']);
});

Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy']);
});
```

### Escenario 3: Validación en controlador

```php
// InvoiceController.php
public function destroy(Request $request, Invoice $invoice)
{
    $user = $request->user();
    
    // Solo admin puede eliminar facturas
    if (!$user->isAdmin()) {
        return response()->json([
            'success' => false,
            'message' => 'Solo los administradores pueden eliminar facturas'
        ], 403);
    }
    
    // Verificar si la factura tiene pagos
    if ($invoice->payments()->count() > 0) {
        return response()->json([
            'success' => false,
            'message' => 'No se puede eliminar una factura con pagos registrados'
        ], 422);
    }
    
    $invoice->delete();
    
    return response()->json([
        'success' => true,
        'message' => 'Factura eliminada exitosamente'
    ]);
}
```

## 🚀 Propuesta de Implementación para tu API

### Usuarios y Administración (Solo Admin)
```php
Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/role', [UserController::class, 'changeRole']);
});
```

### Proveedores y Compras (Solo Admin)
```php
Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('purchases', PurchaseController::class);
    Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus']);
    Route::patch('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
});
```

### Productos (Admin crea/edita, Cajero solo ve)
```php
// Admin puede todo
Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
    Route::patch('products/{product}/stock', [ProductController::class, 'updateStock']);
});

// Cajero solo lectura
Route::middleware(['jwt', 'role:admin,cashier'])->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
});
```

### Facturas y Pagos (Ambos roles)
```php
Route::middleware(['jwt', 'role:admin,cashier'])->group(function () {
    Route::apiResource('invoices', InvoiceController::class)->except(['destroy']);
    Route::apiResource('payments', PaymentController::class)->except(['destroy']);
    Route::apiResource('customers', CustomerController::class);
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
    Route::get('invoices/{invoice_id}/payments', [PaymentController::class, 'getByInvoice']);
});

// Solo admin puede eliminar
Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy']);
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy']);
});
```

### Reportes y Estadísticas (Solo Admin)
```php
Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::get('purchases-stats', [PurchaseController::class, 'stats']);
    Route::get('quotes-stats', [QuoteController::class, 'stats']);
    Route::get('inventory-stats', [InventoryMovementController::class, 'movementStats']);
    Route::get('inventory-export', [InventoryMovementController::class, 'export']);
});
```

## 🔒 Frontend: Solo para UX, NO para Seguridad

### ✅ En el Frontend (React/Vue/etc):
```javascript
// Solo para mostrar/ocultar elementos de UI
const user = useAuthStore().user;

return (
  <div>
    {user.role === 'admin' && (
      <button onClick={deleteProduct}>Eliminar Producto</button>
    )}
    
    {user.role === 'cashier' && (
      <p>No tienes permisos para eliminar</p>
    )}
  </div>
);
```

### ❌ NUNCA hagas esto (inseguro):
```javascript
// ❌ MAL - Solo confiar en el frontend
if (user.role === 'admin') {
  await api.delete('/products/123'); // Cualquiera puede llamar esto
}
```

### ✅ Siempre así:
```javascript
// ✅ BIEN - El backend valida siempre
try {
  await api.delete('/products/123');
  // Si llega aquí, el backend validó que tiene permisos
} catch (error) {
  if (error.status === 403) {
    showError('No tienes permisos para esta acción');
  }
}
```

## 🎯 Respuesta a tu Pregunta

**¿Dónde se hace la validación de roles?**

1. **🔒 BACKEND (OBLIGATORIO)**: Siempre en el servidor
   - Middleware de roles
   - Validación en controladores
   - Base de datos como fuente de verdad

2. **🎨 FRONTEND (OPCIONAL)**: Solo para UX
   - Mostrar/ocultar botones
   - Navegación condicional
   - Mensajes de usuario

**Regla de oro**: El frontend puede mentir, el backend nunca debe confiar en él.

## 🚀 ¿Quieres que implemente esto en tu API?

Puedo:
1. ✅ Configurar las rutas con restricciones de roles
2. ✅ Actualizar los controladores con validaciones
3. ✅ Agregar métodos helper al modelo User
4. ✅ Crear middleware adicional si lo necesitas

¿En qué parte específica te gustaría que empiece?
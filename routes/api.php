<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\InventoryMovementController;

// Rutas públicas (sin autenticación)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Rutas protegidas (con autenticación JWT)
Route::middleware('jwt')->group(function () {
    
    // Rutas de autenticación
    Route::prefix('auth')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Rutas de clientes
    Route::apiResource('customers', CustomerController::class);

    // Rutas de productos
    Route::apiResource('products', ProductController::class);
    Route::patch('products/{product}/stock', [ProductController::class, 'updateStock']);

    // Rutas de facturas
    Route::apiResource('invoices', InvoiceController::class);
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
    
    // Rutas de pagos
    Route::apiResource('payments', PaymentController::class);
    Route::get('invoices/{invoice_id}/payments', [PaymentController::class, 'getByInvoice']);
    
    // Rutas de usuarios (administración)
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/role', [UserController::class, 'changeRole']);
    
    // Rutas de proveedores
    Route::apiResource('suppliers', SupplierController::class);
    Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus']);
    Route::get('suppliers-active', [SupplierController::class, 'activeSuppliers']);
    
    // Rutas de compras
    Route::apiResource('purchases', PurchaseController::class);
    Route::patch('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
    Route::patch('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel']);
    Route::get('purchases-stats', [PurchaseController::class, 'stats']);
    
    // Rutas de cotizaciones
    Route::apiResource('quotes', QuoteController::class);
    Route::patch('quotes/{quote}/send', [QuoteController::class, 'send']);
    Route::patch('quotes/{quote}/approve', [QuoteController::class, 'approve']);
    Route::patch('quotes/{quote}/reject', [QuoteController::class, 'reject']);
    Route::post('quotes/{quote}/convert-to-invoice', [QuoteController::class, 'convertToInvoice']);
    Route::patch('quotes/mark-expired', [QuoteController::class, 'markExpired']);
    Route::get('quotes-stats', [QuoteController::class, 'stats']);
    
    // Rutas de movimientos de inventario
    Route::get('inventory-movements', [InventoryMovementController::class, 'index']);
    Route::post('inventory-adjustments', [InventoryMovementController::class, 'createAdjustment']);
    Route::get('inventory-movements/product/{product}', [InventoryMovementController::class, 'showByProduct']);
    Route::get('inventory-summary', [InventoryMovementController::class, 'inventorySummary']);
    Route::get('inventory-stats', [InventoryMovementController::class, 'movementStats']);
    Route::get('inventory-alerts', [InventoryMovementController::class, 'inventoryAlerts']);
    Route::get('inventory-export', [InventoryMovementController::class, 'export']);
    
});
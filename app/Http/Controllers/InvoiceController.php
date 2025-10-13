<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    /**
     * Listar todas las facturas
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer', 'user', 'items.product']);

        // Filtrar por estado
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filtrar por cliente
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Buscar por nombre de cliente
        if ($request->has('customer_name')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer_name . '%');
            });
        }

        $invoices = $query->paginate($request->get('per_page', 15));

        return response()->json($invoices);
    }

    /**
     * Crear nueva factura
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'invoice_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            try {
                // Obtener tax_rate del config o usar default
                $taxRate = config('app.tax_rate', 0.12);
                
                // Crear la factura
                $invoice = Invoice::create([
                    'customer_id' => $validatedData['customer_id'],
                    'user_id' => $request->user()->id,
                    'invoice_date' => $validatedData['invoice_date'],
                    'tax_rate' => $taxRate,
                    'total' => 0, // Se calculará después
                    'status' => 'pending'
                ]);

                // Crear los items de la factura
                foreach ($validatedData['items'] as $itemData) {
                    $product = Product::findOrFail($itemData['product_id']);
                    
                    // Verificar stock disponible
                    if ($product->stock < $itemData['quantity']) {
                        throw new \Exception("Stock insuficiente para el producto: {$product->name}");
                    }

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price']
                    ]);

                    // Crear movimiento de inventario y actualizar stock
                    InventoryMovement::createMovement(
                        $itemData['product_id'],
                        'sale',
                        $itemData['quantity'],
                        $invoice->id,
                        'sale',
                        "Venta - Factura #{$invoice->id}",
                        $invoice->user_id
                    );
                }

                // Calcular totales como en sistema PHP vanilla
                $invoice->load('items'); // Cargar items para cálculo
                $invoice->calculateTotals();

                DB::commit();

                $invoice->load(['customer', 'user', 'items.product', 'payments']);

                return response()->json([
                    'message' => 'Factura creada exitosamente',
                    'invoice' => $invoice,
                    'breakdown' => [
                        'subtotal' => $invoice->subtotal,
                        'tax_amount' => $invoice->tax_amount,
                        'tax_rate' => $invoice->tax_rate,
                        'total' => $invoice->total,
                        'balance_due' => $invoice->balance_due
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => $e->getMessage()
                ], 400);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Mostrar factura específica
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['customer', 'user', 'items.product', 'payments']);
        
        return response()->json([
            'invoice' => $invoice,
            'breakdown' => [
                'subtotal' => $invoice->subtotal,
                'tax_amount' => $invoice->tax_amount,
                'tax_rate' => $invoice->tax_rate,
                'total' => $invoice->total,
                'balance_due' => $invoice->balance_due,
                'total_paid' => $invoice->payments->sum('amount'),
                'is_paid' => $invoice->isPaid(),
                'is_partial' => $invoice->isPartiallyPaid()
            ]
        ]);
    }

    /**
     * Actualizar estado de la factura
     */
    public function updateStatus(Request $request, Invoice $invoice): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|in:paid,pending,canceled'
            ]);

            DB::beginTransaction();

            // Si se cancela la factura, restaurar stock y eliminar items
            if ($validatedData['status'] === 'canceled' && $invoice->status !== 'canceled') {
                // Restaurar stock de los productos
                foreach ($invoice->items as $item) {
                    $item->product->increment('stock', $item->quantity);
                }

                // Eliminar todos los items de la factura
                $invoice->items()->delete();

                // Actualizar totales a 0
                $invoice->update([
                    'status' => 'canceled',
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total' => 0,
                    'balance_due' => 0
                ]);
            } else {
                $invoice->update(['status' => $validatedData['status']]);
            }

            DB::commit();

            // Recargar la factura con sus relaciones actualizadas
            $invoice->load(['customer', 'user', 'items.product', 'payments']);

            return response()->json([
                'message' => 'Estado de factura actualizado exitosamente',
                'invoice' => $invoice
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar factura completa
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        // Solo se pueden editar facturas pendientes
        if ($invoice->status !== 'pending') {
            return response()->json([
                'message' => 'Solo se pueden editar facturas en estado pendiente'
            ], 409);
        }

        try {
            $validatedData = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'invoice_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            try {
                // Restaurar stock de los items anteriores
                foreach ($invoice->items as $item) {
                    $item->product->increment('stock', $item->quantity);
                }

                // Eliminar items anteriores
                $invoice->items()->delete();

                // Actualizar datos básicos de la factura
                $invoice->update([
                    'customer_id' => $validatedData['customer_id'],
                    'invoice_date' => $validatedData['invoice_date']
                ]);

                // Crear los nuevos items
                foreach ($validatedData['items'] as $itemData) {
                    $product = Product::findOrFail($itemData['product_id']);
                    
                    // Verificar stock disponible
                    if ($product->stock < $itemData['quantity']) {
                        throw new \Exception("Stock insuficiente para el producto: {$product->name}");
                    }

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price']
                    ]);

                    // Actualizar stock del producto
                    $product->decrement('stock', $itemData['quantity']);
                }

                // Recalcular totales
                $invoice->load('items');
                $invoice->calculateTotals();

                DB::commit();

                $invoice->load(['customer', 'user', 'items.product', 'payments']);

                return response()->json([
                    'message' => 'Factura actualizada exitosamente',
                    'invoice' => $invoice,
                    'breakdown' => [
                        'subtotal' => $invoice->subtotal,
                        'tax_amount' => $invoice->tax_amount,
                        'tax_rate' => $invoice->tax_rate,
                        'total' => $invoice->total,
                        'balance_due' => $invoice->balance_due
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => $e->getMessage()
                ], 400);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Eliminar factura (solo si está en pending)
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->status === 'paid') {
            return response()->json([
                'message' => 'No se pueden eliminar facturas pagadas'
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Restaurar stock de productos
            foreach ($invoice->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            $invoice->delete();

            DB::commit();

            return response()->json([
                'message' => 'Factura eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la factura: ' . $e->getMessage()
            ], 500);
        }
    }
}
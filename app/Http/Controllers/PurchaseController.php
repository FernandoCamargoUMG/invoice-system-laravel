<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    /**
     * Listar todas las compras
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Purchase::with(['supplier', 'user', 'items.product']);

            // Filtros
            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->get('supplier_id'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('date_from')) {
                $query->where('purchase_date', '>=', $request->get('date_from'));
            }

            if ($request->has('date_to')) {
                $query->where('purchase_date', '<=', $request->get('date_to'));
            }

            // Búsqueda por número de compra
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('purchase_number', 'like', "%{$search}%");
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $purchases = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $purchases
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las compras',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva compra
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'purchase_date' => 'required|date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.cost_price' => 'required|numeric|min:0.01'
            ]);

            // Requerir usuario autenticado (sin fallback)
            $userId = Auth::id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado. Debe iniciar sesión para crear compras.'
                ], 401);
            }

            DB::beginTransaction();

            // Calcular totales CON LÓGICA DE GUATEMALA
            // En compras, el precio ya viene CON IVA incluido
            $taxRate = 0.12; // 12% IVA Guatemala
            $total = 0;
            $subtotal = 0;
            
            foreach ($validated['items'] as $itemData) {
                $totalItemConIva = $itemData['quantity'] * $itemData['cost_price'];
                $total += $totalItemConIva;
                
                // Separar el IVA: precio sin IVA = precio con IVA / (1 + tasa IVA)
                $subtotalItem = $totalItemConIva / (1 + $taxRate);
                $subtotal += $subtotalItem;
            }
            
            $taxAmount = $total - $subtotal;

            // Crear la compra CON todos los campos requeridos
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'user_id' => $userId, // Usuario autenticado (sin fallback)
                'purchase_number' => Purchase::generatePurchaseNumber(),
                'purchase_date' => $validated['purchase_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'tax_rate' => $taxRate,
                'total' => $total
            ]);

            // Crear los items de la compra
            foreach ($validated['items'] as $itemData) {
                $totalCost = $itemData['quantity'] * $itemData['cost_price'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'cost_price' => $itemData['cost_price'],
                    'total_cost' => $totalCost
                ]);
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $purchase->load(['supplier', 'user', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Compra creada exitosamente',
                'data' => $purchase
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una compra específica
     */
    public function show(Purchase $purchase): JsonResponse
    {
        try {
            $purchase->load(['supplier', 'user', 'items.product']);

            return response()->json([
                'success' => true,
                'data' => $purchase
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una compra (solo si está pendiente)
     */
    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        try {
            if ($purchase->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar compras pendientes'
                ], 422);
            }

            $validated = $request->validate([
                'supplier_id' => 'sometimes|required|exists:suppliers,id',
                'purchase_date' => 'sometimes|required|date',
                'notes' => 'nullable|string',
                'items' => 'sometimes|required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.cost_price' => 'required|numeric|min:0.01'
            ]);

            DB::beginTransaction();

            // Actualizar datos básicos
            $purchase->update([
                'supplier_id' => $validated['supplier_id'] ?? $purchase->supplier_id,
                'purchase_date' => $validated['purchase_date'] ?? $purchase->purchase_date,
                'notes' => $validated['notes'] ?? $purchase->notes
            ]);

            // Si se actualizan los items
            if (isset($validated['items'])) {
                // Eliminar items existentes
                $purchase->items()->delete();

                $subtotal = 0;

                // Crear nuevos items
                foreach ($validated['items'] as $itemData) {
                    $totalCost = $itemData['quantity'] * $itemData['cost_price'];
                    $subtotal += $totalCost;

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'cost_price' => $itemData['cost_price'],
                        'total_cost' => $totalCost
                    ]);
                }

                // Recalcular totales
                $taxRate = 0.12;
                $taxAmount = $subtotal * $taxRate;
                $total = $subtotal + $taxAmount;

                $purchase->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total
                ]);
            }

            DB::commit();

            $purchase->load(['supplier', 'user', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Compra actualizada exitosamente',
                'data' => $purchase
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recibir mercancía (actualizar inventario)
     */
    public function receive(Purchase $purchase): JsonResponse
    {
        try {
            if ($purchase->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden recibir compras pendientes'
                ], 422);
            }

            // Requerir usuario autenticado
            $userId = Auth::id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado. Debe iniciar sesión para recibir compras.'
                ], 401);
            }

            DB::beginTransaction();

            // Actualizar inventario para cada item
            foreach ($purchase->items as $item) {
                $product = $item->product;
                // Actualizar precio de costo si es diferente
                if ($product->cost_price != $item->cost_price) {
                    $product->update(['cost_price' => $item->cost_price]);
                }

                // Registrar movimiento de inventario (también actualiza el stock)
                InventoryMovement::createMovement(
                    $product,
                    'purchase',
                    $item->quantity,
                    'purchase',
                    $purchase->id,
                    "Compra #{$purchase->purchase_number}",
                    $userId
                );
            }

            // Cambiar estado a recibido
            $purchase->update(['status' => 'received']);

            DB::commit();

            $purchase->load(['supplier', 'user', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Mercancía recibida exitosamente',
                'data' => $purchase
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al recibir la mercancía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una compra
     */
    public function cancel(Purchase $purchase): JsonResponse
    {
        try {
            if ($purchase->status === 'received') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una compra ya recibida'
                ], 422);
            }

            $purchase->update(['status' => 'canceled']);

            return response()->json([
                'success' => true,
                'message' => 'Compra cancelada exitosamente',
                'data' => $purchase
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una compra (solo si está cancelada o pendiente)
     */
    public function destroy(Purchase $purchase): JsonResponse
    {
        try {
            if ($purchase->status === 'received') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una compra ya recibida'
                ], 422);
            }

            DB::beginTransaction();

            // Eliminar items primero
            $purchase->items()->delete();
            
            // Eliminar la compra
            $purchase->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de compras
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->startOfMonth());
            $dateTo = $request->get('date_to', now()->endOfMonth());

            $stats = [
                'total_purchases' => Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])->count(),
                'total_amount' => Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])->sum('total'),
                'pending_purchases' => Purchase::where('status', 'pending')->count(),
                'received_purchases' => Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])
                                                ->where('status', 'received')->count(),
                'top_suppliers' => Purchase::selectRaw('supplier_id, suppliers.name, COUNT(*) as total_purchases, SUM(total) as total_amount')
                                          ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                                          ->whereBetween('purchase_date', [$dateFrom, $dateTo])
                                          ->groupBy('supplier_id', 'suppliers.name')
                                          ->orderByDesc('total_amount')
                                          ->limit(5)
                                          ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryMovementController extends Controller
{
    /**
     * Listar todos los movimientos de inventario
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = InventoryMovement::with(['product', 'user']);

            // Filtros
            if ($request->has('product_id')) {
                $query->where('product_id', $request->get('product_id'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            if ($request->has('reference_type')) {
                $query->where('reference_type', $request->get('reference_type'));
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->get('date_from') . ' 00:00:00');
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->get('date_to') . ' 23:59:59');
            }

            // Búsqueda por nombre de producto
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->whereHas('product', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            // Paginación
            $perPage = $request->get('per_page', 20);
            $movements = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $movements
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los movimientos de inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un ajuste manual de inventario
     */
    public function createAdjustment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|not_in:0',
                'notes' => 'required|string|max:500'
            ]);

            DB::beginTransaction();

            $product = Product::find($validated['product_id']);
            $oldStock = $product->stock;
            $newStock = $oldStock + $validated['quantity'];

            // Verificar que el stock no quede negativo
            if ($newStock < 0) {
                throw new \Exception('El ajuste resultaría en stock negativo');
            }

            // Actualizar stock del producto
            $product->update(['stock' => $newStock]);

            // Crear movimiento de inventario
            $movement = InventoryMovement::createMovement(
                $product->id,
                'adjustment',
                $validated['quantity'],
                $oldStock,
                $newStock,
                'manual',
                null,
                $validated['notes'],
                Auth::id()
            );

            DB::commit();

            $movement->load(['product', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Ajuste de inventario creado exitosamente',
                'data' => $movement
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
                'message' => 'Error al crear el ajuste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar movimientos de un producto específico
     */
    public function showByProduct(Product $product, Request $request): JsonResponse
    {
        try {
            $query = $product->inventoryMovements()->with(['user']);

            // Filtros
            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->get('date_from') . ' 00:00:00');
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->get('date_to') . ' 23:59:59');
            }

            $perPage = $request->get('per_page', 15);
            $movements = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product,
                    'movements' => $movements
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los movimientos del producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen de inventario
     */
    public function inventorySummary(Request $request): JsonResponse
    {
        try {
            $query = Product::query();

            // Filtros
            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            if ($request->has('low_stock')) {
                if ($request->boolean('low_stock')) {
                    $query->where('stock', '<=', 10); // Stock bajo: 10 o menos
                }
            }

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('name', 'like', "%{$search}%");
            }

            $products = $query->orderBy('name')->get();

            // Calcular totales
            $summary = [
                'total_products' => $products->count(),
                'total_stock_value' => $products->sum(function($product) {
                    return $product->stock * ($product->cost_price ?? $product->price);
                }),
                'low_stock_products' => $products->where('stock', '<=', 10)->count(),
                'out_of_stock_products' => $products->where('stock', 0)->count(),
                'products' => $products->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'type' => $product->type,
                        'sku' => $product->sku,
                        'stock' => $product->stock,
                        'price' => $product->price,
                        'cost_price' => $product->cost_price,
                        'stock_value' => $product->stock * ($product->cost_price ?? $product->price),
                        'stock_status' => $product->stock == 0 ? 'sin_stock' : 
                                        ($product->stock <= 10 ? 'stock_bajo' : 'normal')
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el resumen de inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de movimientos
     */
    public function movementStats(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->startOfMonth());
            $dateTo = $request->get('date_to', now()->endOfMonth());

            $stats = [
                'total_movements' => InventoryMovement::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'movements_by_type' => InventoryMovement::selectRaw('type, COUNT(*) as count')
                                                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                                                        ->groupBy('type')
                                                        ->get(),
                'top_moved_products' => InventoryMovement::selectRaw('product_id, products.name, COUNT(*) as movement_count, SUM(ABS(quantity)) as total_quantity')
                                                         ->join('products', 'inventory_movements.product_id', '=', 'products.id')
                                                         ->whereBetween('inventory_movements.created_at', [$dateFrom, $dateTo])
                                                         ->groupBy('product_id', 'products.name')
                                                         ->orderByDesc('total_quantity')
                                                         ->limit(10)
                                                         ->get(),
                'recent_adjustments' => InventoryMovement::where('type', 'adjustment')
                                                         ->whereBetween('created_at', [$dateFrom, $dateTo])
                                                         ->with(['product', 'user'])
                                                         ->latest()
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
                'message' => 'Error al obtener estadísticas de movimientos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener alertas de inventario
     */
    public function inventoryAlerts(): JsonResponse
    {
        try {
            $alerts = [
                'out_of_stock' => Product::where('stock', 0)->orderBy('name')->get(['id', 'name', 'type', 'stock']),
                'low_stock' => Product::where('stock', '>', 0)
                                     ->where('stock', '<=', 10)
                                     ->orderBy('stock')
                                     ->get(['id', 'name', 'type', 'stock']),
                'no_cost_price' => Product::whereNull('cost_price')
                                         ->orWhere('cost_price', 0)
                                         ->orderBy('name')
                                         ->get(['id', 'name', 'type', 'price', 'cost_price'])
            ];

            $alertCount = count($alerts['out_of_stock']) + count($alerts['low_stock']) + count($alerts['no_cost_price']);

            return response()->json([
                'success' => true,
                'data' => $alerts,
                'total_alerts' => $alertCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener alertas de inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar movimientos de inventario
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = InventoryMovement::with(['product', 'user']);

            // Aplicar filtros
            if ($request->has('product_id')) {
                $query->where('product_id', $request->get('product_id'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->get('date_from') . ' 00:00:00');
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->get('date_to') . ' 23:59:59');
            }

            $movements = $query->latest()->get();

            $exportData = $movements->map(function($movement) {
                return [
                    'fecha' => $movement->created_at->format('d/m/Y H:i:s'),
                    'producto' => $movement->product->name,
                    'tipo' => $movement->type,
                    'cantidad' => $movement->quantity,
                    'stock_anterior' => $movement->stock_before,
                    'stock_posterior' => $movement->stock_after,
                    'referencia' => $movement->reference_type,
                    'usuario' => $movement->user->name,
                    'notas' => $movement->notes
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'total_records' => $exportData->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar movimientos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    /**
     * Listar todas las cotizaciones
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Quote::with(['customer', 'user', 'items.product']);

            // Filtros
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->get('customer_id'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('date_from')) {
                $query->where('quote_date', '>=', $request->get('date_from'));
            }

            if ($request->has('date_to')) {
                $query->where('quote_date', '<=', $request->get('date_to'));
            }

            // Búsqueda por número de cotización
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('quote_number', 'like', "%{$search}%");
            }

            // Filtrar cotizaciones expiradas
            if ($request->boolean('include_expired', true) === false) {
                $query->where('valid_until', '>=', now()->toDateString());
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $quotes = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $quotes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cotizaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva cotización
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'quote_date' => 'required|date',
                'valid_until' => 'required|date|after:quote_date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0.01'
            ]);

            DB::beginTransaction();

            // Verificar que la petición venga de un usuario autenticado
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            // Verificar stock disponible
            foreach ($validated['items'] as $itemData) {
                $product = Product::find($itemData['product_id']);
                if ($product->stock < $itemData['quantity']) {
                    throw new \Exception("Stock insuficiente para el producto: {$product->name}. Stock disponible: {$product->stock}");
                }
            }

            // Crear la cotización con datos mínimos (totales en 0, se recalculan después)
            $quote = Quote::create([
                'customer_id' => $validated['customer_id'],
                'user_id' => $user->id,
                'quote_number' => Quote::generateQuoteNumber(),
                'quote_date' => $validated['quote_date'],
                'valid_until' => $validated['valid_until'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'subtotal' => 0,
                'tax_amount' => 0,
                'tax_rate' => 0,
                'total' => 0
            ]);

            // Crear los items de la cotización
            foreach ($validated['items'] as $itemData) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    // total_price se calcula automáticamente en el modelo
                ]);
            }

            // Calcular y actualizar totales usando la lógica centralizada del modelo
            $quote->load('items');
            $quote->calculateTotals();

            DB::commit();

            // Cargar relaciones para la respuesta
            $quote->load(['customer', 'user', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
                'data' => $quote
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
                'message' => 'Error al crear la cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Actualizar una cotización (solo si está en borrador)
     */
    public function update(Request $request, Quote $quote): JsonResponse
    {
        try {
            if (!in_array($quote->status, ['draft', 'sent'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar cotizaciones en borrador o enviadas'
                ], 422);
            }

            $validated = $request->validate([
                'customer_id' => 'sometimes|required|exists:customers,id',
                'quote_date' => 'sometimes|required|date',
                'valid_until' => 'sometimes|required|date|after:quote_date',
                'notes' => 'nullable|string',
                'items' => 'sometimes|required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0.01'
            ]);

            DB::beginTransaction();

            // Actualizar datos básicos
            $quote->update([
                'customer_id' => $validated['customer_id'] ?? $quote->customer_id,
                'quote_date' => $validated['quote_date'] ?? $quote->quote_date,
                'valid_until' => $validated['valid_until'] ?? $quote->valid_until,
                'notes' => $validated['notes'] ?? $quote->notes
            ]);

            // Si se actualizan los items
            if (isset($validated['items'])) {
                // Verificar stock disponible
                foreach ($validated['items'] as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    if ($product->stock < $itemData['quantity']) {
                        throw new \Exception("Stock insuficiente para el producto: {$product->name}. Stock disponible: {$product->stock}");
                    }
                }

                // Eliminar items existentes
                $quote->items()->delete();

                // Crear nuevos items
                foreach ($validated['items'] as $itemData) {
                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        // total_price se calcula automáticamente en el modelo
                    ]);
                }

                // Recalcular totales usando la lógica centralizada del modelo
                $quote->load('items');
                $quote->calculateTotals();
            }

            DB::commit();

            $quote->load(['customer', 'user', 'items.product']);

            return response()->json([
                'success' => true,
                'message' => 'Cotización actualizada exitosamente',
                'data' => $quote
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
                'message' => 'Error al actualizar la cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar cotización al cliente
     */
    public function send(Quote $quote): JsonResponse
    {
        try {
            if ($quote->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden enviar cotizaciones en borrador'
                ], 422);
            }

            $quote->update(['status' => 'sent']);

            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada exitosamente',
                'data' => $quote
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar cotización
     */
    public function approve(Quote $quote): JsonResponse
    {
        try {
            if ($quote->status !== 'sent') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar cotizaciones enviadas'
                ], 422);
            }

            // Verificar que la cotización no esté vencida antes de aprobar
            if ($quote->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización ha expirado o no puede ser aprobada'
                ], 422);
            }

            $quote->update(['status' => 'approved']);

            return response()->json([
                'success' => true,
                'message' => 'Cotización aprobada exitosamente',
                'data' => $quote
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar cotización
     */
    public function reject(Quote $quote): JsonResponse
    {
        try {
            if (!in_array($quote->status, ['sent', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar cotizaciones enviadas o aprobadas'
                ], 422);
            }

            $quote->update(['status' => 'rejected']);

            return response()->json([
                'success' => true,
                'message' => 'Cotización rechazada',
                'data' => $quote
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convertir cotización a factura
     */
    public function convertToInvoice(Quote $quote): JsonResponse
    {
        try {
            if (!$quote->canBeConverted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no puede ser convertida. Debe estar aprobada y dentro del período de vigencia'
                ], 422);
            }

            $invoice = $quote->convertToInvoice();

            return response()->json([
                'success' => true,
                'message' => 'Cotización convertida a factura exitosamente',
                'data' => [
                    'quote' => $quote->load(['customer', 'user', 'items.product']),
                    'invoice' => $invoice->load(['customer', 'user', 'items.product'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al convertir la cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una cotización
     */
    public function destroy(Quote $quote): JsonResponse
    {
        try {
            if ($quote->status === 'converted') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una cotización convertida a factura'
                ], 422);
            }

            DB::beginTransaction();

            // Eliminar items primero
            $quote->items()->delete();
            
            // Eliminar la cotización
            $quote->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar cotizaciones expiradas
     */
    public function markExpired(): JsonResponse
    {
        try {
            $expiredCount = Quote::where('valid_until', '<', now()->toDateString())
                                ->whereNotIn('status', ['converted', 'expired', 'rejected'])
                                ->update(['status' => 'expired']);

            return response()->json([
                'success' => true,
                'message' => "Se marcaron {$expiredCount} cotizaciones como expiradas",
                'expired_count' => $expiredCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar cotizaciones expiradas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de cotizaciones
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->startOfMonth());
            $dateTo = $request->get('date_to', now()->endOfMonth());

            $stats = [
                'total_quotes' => Quote::whereBetween('quote_date', [$dateFrom, $dateTo])->count(),
                'total_amount' => Quote::whereBetween('quote_date', [$dateFrom, $dateTo])->sum('total'),
                'draft_quotes' => Quote::where('status', 'draft')->count(),
                'sent_quotes' => Quote::where('status', 'sent')->count(),
                'approved_quotes' => Quote::where('status', 'approved')->count(),
                'converted_quotes' => Quote::whereBetween('quote_date', [$dateFrom, $dateTo])
                                           ->where('status', 'converted')->count(),
                'expired_quotes' => Quote::where('status', 'expired')->count(),
                'conversion_rate' => 0
            ];

            // Calcular tasa de conversión
            $totalQuotes = $stats['total_quotes'];
            if ($totalQuotes > 0) {
                $stats['conversion_rate'] = round(($stats['converted_quotes'] / $totalQuotes) * 100, 2);
            }

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
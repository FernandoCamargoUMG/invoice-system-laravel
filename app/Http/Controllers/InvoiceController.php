<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
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
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            try {
                // Crear la factura
                $invoice = Invoice::create([
                    'customer_id' => $validatedData['customer_id'],
                    'user_id' => $request->user()->id, // Obtener user_id del middleware
                    'total' => 0, // Se calculará después
                    'status' => 'pending'
                ]);

                $totalAmount = 0;

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

                    // Calcular total
                    $totalAmount += $itemData['quantity'] * $itemData['price'];

                    // Actualizar stock del producto
                    $product->decrement('stock', $itemData['quantity']);
                }

                // Actualizar total de la factura
                $invoice->update(['total' => $totalAmount]);

                DB::commit();

                $invoice->load(['customer', 'user', 'items.product']);

                return response()->json([
                    'message' => 'Factura creada exitosamente',
                    'invoice' => $invoice
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
        $invoice->load(['customer', 'user', 'items.product']);
        return response()->json($invoice);
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

            $invoice->update(['status' => $validatedData['status']]);

            return response()->json([
                'message' => 'Estado de factura actualizado exitosamente',
                'invoice' => $invoice
            ]);

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
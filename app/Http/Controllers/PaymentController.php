<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Listar todos los pagos
     */
    public function index(): JsonResponse
    {
        try {
            $payments = Payment::with(['invoice', 'invoice.customer'])->get();
            
            return response()->json([
                'message' => 'Pagos obtenidos exitosamente',
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener pagos', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al obtener pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un pago específico
     */
    public function show(Payment $payment): JsonResponse
    {
        try {
            $payment->load(['invoice', 'invoice.customer']);
            
            return response()->json([
                'message' => 'Pago obtenido exitosamente',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener pago', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al obtener pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo pago
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:cash,card,transfer,check',
                'payment_date' => 'required|date',
                'notes' => 'nullable|string|max:500'
            ]);

            // Verificar que la factura existe y obtener el total pendiente
            $invoice = Invoice::findOrFail($validatedData['invoice_id']);
            $totalPaid = Payment::where('invoice_id', $invoice->id)->sum('amount');
            $remainingAmount = $invoice->total - $totalPaid;

            // Verificar que el monto no exceda lo pendiente
            if ($validatedData['amount'] > $remainingAmount) {
                return response()->json([
                    'message' => 'El monto del pago excede el saldo pendiente',
                    'remaining_amount' => $remainingAmount
                ], 422);
            }

            $payment = Payment::create($validatedData);
            $payment->load(['invoice', 'invoice.customer']);

            // Verificar si la factura está completamente pagada
            $newTotalPaid = $totalPaid + $validatedData['amount'];
            if ($newTotalPaid >= $invoice->total) {
                $invoice->update(['status' => 'paid']);
            } else {
                $invoice->update(['status' => 'partial']);
            }

            return response()->json([
                'message' => 'Pago creado exitosamente',
                'data' => $payment
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear pago', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al crear pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un pago
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'amount' => 'sometimes|numeric|min:0.01',
                'payment_method' => 'sometimes|in:cash,card,transfer,check',
                'payment_date' => 'sometimes|date',
                'notes' => 'nullable|string|max:500'
            ]);

            // Si se actualiza el monto, verificar que no exceda el total de la factura
            if (isset($validatedData['amount'])) {
                $invoice = $payment->invoice;
                $otherPayments = Payment::where('invoice_id', $invoice->id)
                    ->where('id', '!=', $payment->id)
                    ->sum('amount');
                
                if ($validatedData['amount'] + $otherPayments > $invoice->total) {
                    return response()->json([
                        'message' => 'El monto total de pagos no puede exceder el total de la factura'
                    ], 422);
                }
            }

            $payment->update($validatedData);
            $payment->load(['invoice', 'invoice.customer']);

            // Recalcular el estado de la factura
            $invoice = $payment->invoice;
            $totalPaid = Payment::where('invoice_id', $invoice->id)->sum('amount');
            
            if ($totalPaid >= $invoice->total) {
                $invoice->update(['status' => 'paid']);
            } elseif ($totalPaid > 0) {
                $invoice->update(['status' => 'partial']);
            } else {
                $invoice->update(['status' => 'pending']);
            }

            return response()->json([
                'message' => 'Pago actualizado exitosamente',
                'data' => $payment
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar pago', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al actualizar pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un pago
     */
    public function destroy(Payment $payment): JsonResponse
    {
        try {
            $invoice = $payment->invoice;
            $payment->delete();

            // Recalcular el estado de la factura después de eliminar el pago
            $totalPaid = Payment::where('invoice_id', $invoice->id)->sum('amount');
            
            if ($totalPaid >= $invoice->total) {
                $invoice->update(['status' => 'paid']);
            } elseif ($totalPaid > 0) {
                $invoice->update(['status' => 'partial']);
            } else {
                $invoice->update(['status' => 'pending']);
            }

            return response()->json([
                'message' => 'Pago eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar pago', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al eliminar pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener pagos de una factura específica
     */
    public function getByInvoice($invoice_id): JsonResponse
    {
        try {
            $payments = Payment::where('invoice_id', $invoice_id)
                ->with(['invoice', 'invoice.customer'])
                ->get();

            $invoice = Invoice::findOrFail($invoice_id);
            $totalPaid = $payments->sum('amount');
            $remainingAmount = $invoice->total - $totalPaid;

            return response()->json([
                'message' => 'Pagos de la factura obtenidos exitosamente',
                'data' => [
                    'payments' => $payments,
                    'invoice_total' => $invoice->total,
                    'total_paid' => $totalPaid,
                    'remaining_amount' => $remainingAmount,
                    'status' => $invoice->status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener pagos de factura', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al obtener pagos de factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
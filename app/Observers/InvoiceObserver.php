<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    /**
     * Handle the Invoice "updated" event.
     * Replica la funcionalidad del trigger del sistema PHP vanilla
     */
    public function updated(Invoice $invoice): void
    {
        // Si el estado cambió, manejar pagos automáticamente
        if ($invoice->isDirty('status')) {
            $this->manejarPagoAutomatico($invoice);
        }
    }

    /**
     * Maneja pagos automáticamente como en el sistema PHP vanilla
     * Replica: manejarPago() del InvoiceModel.php original
     */
    private function manejarPagoAutomatico(Invoice $invoice): void
    {
        try {
            switch ($invoice->status) {
                case 'paid':
                    // Si está pagada, crear pago automático si no existe
                    $existingPayment = Payment::where('invoice_id', $invoice->id)->first();
                    if (!$existingPayment) {
                        Payment::create([
                            'invoice_id' => $invoice->id,
                            'amount' => $invoice->total,
                            'payment_date' => now()
                        ]);
                        Log::info("Pago automático creado para factura {$invoice->id}");
                    }
                    break;

                case 'canceled':
                    // Si está cancelada, eliminar pagos existentes
                    Payment::where('invoice_id', $invoice->id)->delete();
                    Log::info("Pagos eliminados para factura cancelada {$invoice->id}");
                    break;

                case 'pending':
                    // No hacer nada para pending
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Error en trigger automático de pagos: " . $e->getMessage());
        }
    }
}
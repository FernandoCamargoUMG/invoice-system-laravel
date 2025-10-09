<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     * Actualiza automáticamente el estado de la factura
     */
    public function created(Payment $payment): void
    {
        $this->updateInvoiceStatus($payment->invoice);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        $this->updateInvoiceStatus($payment->invoice);
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->updateInvoiceStatus($payment->invoice);
    }

    /**
     * Actualiza el estado de la factura basado en pagos
     * Replica la lógica del trigger del sistema original
     * SOLO USA: 'paid', 'pending', 'canceled' como en PHP vanilla
     */
    private function updateInvoiceStatus(Invoice $invoice): void
    {
        try {
            $totalPaid = $invoice->payments()->sum('amount');
            $invoiceTotal = $invoice->total;

            // Solo cambiar entre 'pending' y 'paid' (no 'partial')
            $newStatus = 'pending';
            if ($totalPaid >= $invoiceTotal) {
                $newStatus = 'paid';
            }
            // Si hay pago parcial, sigue siendo 'pending'

            // Solo actualizar si el estado cambió
            if ($invoice->status !== $newStatus) {
                $invoice->update(['status' => $newStatus]);
                Log::info("Estado de factura {$invoice->id} actualizado automáticamente a: {$newStatus}");
            }

            // Actualizar balance_due
            $invoice->update(['balance_due' => $invoiceTotal - $totalPaid]);

        } catch (\Exception $e) {
            Log::error("Error actualizando estado de factura: " . $e->getMessage());
        }
    }
}
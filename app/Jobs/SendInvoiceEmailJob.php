<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Mail\InvoiceCreatedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function handle(): void
    {
        try {
            Log::info('Sending invoice email', ['invoice_id' => $this->invoice->id]);
            
            Mail::to($this->invoice->customer->email)
                ->send(new InvoiceCreatedMail($this->invoice));
                
            Log::info('Invoice email sent successfully', ['invoice_id' => $this->invoice->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice email job failed permanently', [
            'invoice_id' => $this->invoice->id,
            'error' => $exception->getMessage()
        ]);
    }
}
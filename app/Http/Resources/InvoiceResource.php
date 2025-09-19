<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => 'INV-' . str_pad($this->id, 6, '0', STR_PAD_LEFT),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'subtotal' => number_format($this->subtotal, 2),
            'tax_amount' => number_format($this->tax_amount, 2),
            'total' => number_format($this->total, 2),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Calculated fields
            'total_paid' => number_format($this->payments->sum('amount'), 2),
            'remaining_amount' => number_format($this->total - $this->payments->sum('amount'), 2),
            'is_paid' => $this->status === 'paid',
            'is_overdue' => $this->created_at < now()->subDays(30) && $this->status !== 'paid',
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'partial' => 'Pago Parcial',
            'paid' => 'Pagado',
            'cancelled' => 'Cancelado',
            default => 'Desconocido'
        };
    }
}

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
    }
}

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => $this->quantity,
            'price' => number_format($this->price, 2),
            'subtotal' => number_format($this->quantity * $this->price, 2),
        ];
    }
}

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => number_format($this->price, 2),
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
        ];
    }
}

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => number_format($this->amount, 2),
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date,
            'notes' => $this->notes,
        ];
    }
}
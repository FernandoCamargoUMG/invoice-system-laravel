<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // Usuario admin
        $customers = Customer::take(5)->get();
        $products = Product::take(10)->get();

        $quotes = [
            [
                'customer_id' => $customers[0]->id,
                'user_id' => $user->id,
                'quote_number' => 'QUO-001',
                'quote_date' => '2025-10-01',
                'valid_until' => '2025-10-31',
                'status' => 'approved',
                'notes' => 'Cotización para proyecto de oficina - Aprobada',
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 5, 'price' => 25.99],
                    ['product_id' => $products[1]->id, 'quantity' => 3, 'price' => 45.50],
                ]
            ],
            [
                'customer_id' => $customers[1]->id,
                'user_id' => $user->id,
                'quote_number' => 'QUO-002',
                'quote_date' => '2025-10-03',
                'valid_until' => '2025-11-03',
                'status' => 'sent',
                'notes' => 'Cotización para cliente mayorista - Enviada por email',
                'items' => [
                    ['product_id' => $products[2]->id, 'quantity' => 20, 'price' => 18.75],
                    ['product_id' => $products[3]->id, 'quantity' => 15, 'price' => 12.90],
                    ['product_id' => $products[4]->id, 'quantity' => 8, 'price' => 55.00],
                ]
            ],
            [
                'customer_id' => $customers[2]->id,
                'user_id' => $user->id,
                'quote_number' => 'QUO-003',
                'quote_date' => '2025-10-06',
                'valid_until' => '2025-10-20',
                'status' => 'draft',
                'notes' => 'Borrador - cotización en proceso de revisión',
                'items' => [
                    ['product_id' => $products[5]->id, 'quantity' => 10, 'price' => 9.99],
                    ['product_id' => $products[6]->id, 'quantity' => 25, 'price' => 22.50],
                ]
            ],
            [
                'customer_id' => $customers[3]->id,
                'user_id' => $user->id,
                'quote_number' => 'QUO-004',
                'quote_date' => '2025-10-08',
                'valid_until' => '2025-11-15',
                'status' => 'sent',
                'notes' => 'Cotización especial con descuentos por volumen',
                'items' => [
                    ['product_id' => $products[7]->id, 'quantity' => 50, 'price' => 65.00],
                    ['product_id' => $products[8]->id, 'quantity' => 100, 'price' => 4.25],
                ]
            ],
            [
                'customer_id' => $customers[4]->id,
                'user_id' => $user->id,
                'quote_number' => 'QUO-005',
                'quote_date' => '2025-09-25',
                'valid_until' => '2025-10-10',
                'status' => 'expired',
                'notes' => 'Cotización expirada - cliente no respondió a tiempo',
                'items' => [
                    ['product_id' => $products[9]->id, 'quantity' => 12, 'price' => 35.75],
                ]
            ],
            [
                'customer_id' => $customers[0]->id,
                'user_id' => $user->id,
                'quote_number' => 'QUO-006',
                'quote_date' => '2025-10-09',
                'valid_until' => '2025-11-30',
                'status' => 'rejected',
                'notes' => 'Cotización rechazada por el cliente - precio muy alto',
                'items' => [
                    ['product_id' => $products[1]->id, 'quantity' => 2, 'price' => 89.99],
                    ['product_id' => $products[3]->id, 'quantity' => 5, 'price' => 67.50],
                ]
            ]
        ];

        foreach ($quotes as $quoteData) {
            $items = $quoteData['items'];
            unset($quoteData['items']);

            // Calcular totales
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }
            
            $taxRate = 0.12; // 12% IVA
            $taxAmount = $subtotal * $taxRate;
            $total = $subtotal + $taxAmount;

            $quoteData['subtotal'] = $subtotal;
            $quoteData['tax_rate'] = $taxRate;
            $quoteData['tax_amount'] = $taxAmount;
            $quoteData['total'] = $total;

            $quote = Quote::create($quoteData);

            // Crear items
            foreach ($items as $itemData) {
                $itemData['quote_id'] = $quote->id;
                QuoteItem::create($itemData);
            }
        }
    }
}

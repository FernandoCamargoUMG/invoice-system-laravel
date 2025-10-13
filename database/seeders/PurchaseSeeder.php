<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\User;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // Usuario admin
        $suppliers = Supplier::where('status', 'active')->get();
        $products = Product::take(10)->get();

        $purchases = [
            [
                'supplier_id' => $suppliers->first()->id,
                'user_id' => $user->id,
                'purchase_number' => 'PUR-001',
                'purchase_date' => '2025-10-01',
                'status' => 'received',
                'notes' => 'Compra de productos electrónicos para inventario inicial',
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 50, 'cost_price' => 15.00],
                    ['product_id' => $products[1]->id, 'quantity' => 30, 'cost_price' => 25.50],
                ]
            ],
            [
                'supplier_id' => $suppliers->skip(1)->first()->id,
                'user_id' => $user->id,
                'purchase_number' => 'PUR-002',
                'purchase_date' => '2025-10-05',
                'status' => 'received',
                'notes' => 'Reposición de stock - productos importados',
                'items' => [
                    ['product_id' => $products[2]->id, 'quantity' => 25, 'cost_price' => 12.75],
                    ['product_id' => $products[3]->id, 'quantity' => 40, 'cost_price' => 8.90],
                    ['product_id' => $products[4]->id, 'quantity' => 15, 'cost_price' => 35.00],
                ]
            ],
            [
                'supplier_id' => $suppliers->skip(2)->first()->id,
                'user_id' => $user->id,
                'purchase_number' => 'PUR-003',
                'purchase_date' => '2025-10-08',
                'status' => 'pending',
                'notes' => 'Compra pendiente de recibir - productos de consumo',
                'items' => [
                    ['product_id' => $products[5]->id, 'quantity' => 60, 'cost_price' => 7.25],
                    ['product_id' => $products[6]->id, 'quantity' => 35, 'cost_price' => 18.50],
                ]
            ],
            [
                'supplier_id' => $suppliers->first()->id,
                'user_id' => $user->id,
                'purchase_number' => 'PUR-004',
                'purchase_date' => '2025-10-10',
                'status' => 'received',
                'notes' => 'Compra urgente para cubrir demanda alta',
                'items' => [
                    ['product_id' => $products[7]->id, 'quantity' => 20, 'cost_price' => 45.00],
                    ['product_id' => $products[8]->id, 'quantity' => 80, 'cost_price' => 3.50],
                ]
            ]
        ];

        foreach ($purchases as $purchaseData) {
            $items = $purchaseData['items'];
            unset($purchaseData['items']);

            // Calcular total
            $total = 0;
            foreach ($items as $item) {
                $total += $item['quantity'] * $item['cost_price'];
            }
            $purchaseData['total'] = $total;

            $purchase = Purchase::create($purchaseData);

            // Crear items
            foreach ($items as $itemData) {
                $itemData['purchase_id'] = $purchase->id;
                PurchaseItem::create($itemData);

                // Si la compra está recibida, actualizar stock
                if ($purchase->status === 'received') {
                    $product = Product::find($itemData['product_id']);
                    $product->increment('stock', $itemData['quantity']);
                }
            }
        }
    }
}

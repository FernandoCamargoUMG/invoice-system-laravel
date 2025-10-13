<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\Purchase;

class InventoryMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // Usuario admin
        $products = Product::take(5)->get(); // Usar solo 5 productos
        $purchases = Purchase::where('status', 'received')->get();

        // Crear movimientos básicos para demostrar el sistema
        $movementsData = [
            // Entradas por compras
            ['product_id' => $products[0]->id, 'type' => 'purchase', 'quantity' => 50, 'notes' => 'Entrada por compra - Distribuidora ABC'],
            ['product_id' => $products[1]->id, 'type' => 'purchase', 'quantity' => 30, 'notes' => 'Entrada por compra - Importaciones XYZ'],
            ['product_id' => $products[2]->id, 'type' => 'purchase', 'quantity' => 25, 'notes' => 'Entrada por compra - Mayorista Central'],
            
            // Salidas por ventas
            ['product_id' => $products[0]->id, 'type' => 'sale', 'quantity' => -5, 'notes' => 'Salida por venta - Factura #001'],
            ['product_id' => $products[1]->id, 'type' => 'sale', 'quantity' => -3, 'notes' => 'Salida por venta - Factura #001'],
            
            // Ajustes de inventario
            ['product_id' => $products[2]->id, 'type' => 'adjustment', 'quantity' => 10, 'notes' => 'Ajuste positivo - productos encontrados'],
            ['product_id' => $products[3]->id, 'type' => 'adjustment', 'quantity' => -2, 'notes' => 'Ajuste negativo - productos dañados'],
            
            // Más movimientos
            ['product_id' => $products[4]->id, 'type' => 'purchase', 'quantity' => 40, 'notes' => 'Entrada por compra - Reposición stock'],
            ['product_id' => $products[0]->id, 'type' => 'sale', 'quantity' => -8, 'notes' => 'Salida por venta - Factura #002'],
        ];

        foreach ($movementsData as $index => $movementData) {
            $product = Product::find($movementData['product_id']);
            $stockBefore = $product->stock;
            $stockAfter = $stockBefore + $movementData['quantity'];
            
            // Crear el movimiento
            InventoryMovement::create([
                'product_id' => $movementData['product_id'],
                'user_id' => $user->id,
                'type' => $movementData['type'],
                'quantity' => $movementData['quantity'],
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => $movementData['type'] === 'purchase' ? 'App\\Models\\Purchase' : ($movementData['type'] === 'sale' ? 'App\\Models\\Invoice' : null),
                'reference_id' => $movementData['type'] === 'purchase' ? ($purchases->first()->id ?? 1) : ($movementData['type'] === 'sale' ? 1 : null),
                'notes' => $movementData['notes'],
                'created_at' => now()->subDays(10 - $index)->addHours($index)
            ]);
            
            // Actualizar el stock del producto para el próximo movimiento
            $product->update(['stock' => $stockAfter]);
        }
    }
}

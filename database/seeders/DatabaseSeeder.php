<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@invoice.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        // Crear usuario cajero
        User::create([
            'name' => 'Cajero',
            'email' => 'cajero@invoice.com',
            'password' => Hash::make('password'),
            'role' => 'cashier'
        ]);

        // Crear clientes de prueba
        Customer::create([
            'name' => 'Juan Pérez',
            'email' => 'juan@email.com',
            'phone' => '123456789',
            'address' => 'Calle 123, Ciudad'
        ]);

        Customer::create([
            'name' => 'María García',
            'email' => 'maria@email.com',
            'phone' => '987654321',
            'address' => 'Avenida 456, Ciudad'
        ]);

        // Crear productos de prueba
        Product::create([
            'name' => 'Laptop HP',
            'description' => 'Laptop HP Pavilion 15"',
            'price' => 799.99,
            'stock' => 10
        ]);

        Product::create([
            'name' => 'Mouse Logitech',
            'description' => 'Mouse inalámbrico Logitech',
            'price' => 25.50,
            'stock' => 50
        ]);

        Product::create([
            'name' => 'Teclado Mecánico',
            'description' => 'Teclado mecánico RGB',
            'price' => 89.99,
            'stock' => 25
        ]);

        // Crear más productos para el ERP
        $products = [
            ['name' => 'Monitor Samsung 24"', 'description' => 'Monitor LED Full HD', 'price' => 199.99, 'stock' => 15],
            ['name' => 'Impresora Canon', 'description' => 'Impresora multifuncional', 'price' => 149.99, 'stock' => 8],
            ['name' => 'Disco Duro 1TB', 'description' => 'Disco duro externo', 'price' => 79.99, 'stock' => 20],
            ['name' => 'Memoria RAM 8GB', 'description' => 'Memoria DDR4 8GB', 'price' => 45.99, 'stock' => 30],
            ['name' => 'Webcam Logitech', 'description' => 'Cámara web HD 1080p', 'price' => 65.99, 'stock' => 12],
            ['name' => 'Audífonos Sony', 'description' => 'Audífonos inalámbricos', 'price' => 129.99, 'stock' => 18],
            ['name' => 'Cable HDMI', 'description' => 'Cable HDMI 2 metros', 'price' => 12.99, 'stock' => 100]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Llamar a los nuevos seeders del ERP
        $this->call([
            SupplierSeeder::class,
            PurchaseSeeder::class,
            QuoteSeeder::class,
            InventoryMovementSeeder::class,
        ]);
    }
}

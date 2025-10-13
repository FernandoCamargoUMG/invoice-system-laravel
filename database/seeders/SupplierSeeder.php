<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Distribuidora ABC S.A.',
                'email' => 'ventas@distribuidoraabc.com',
                'phone' => '555-0123',
                'address' => 'Av. Industrial 123, Zona 12, Ciudad',
                'contact_person' => 'Juan Carlos Méndez',
                'tax_id' => '1234567890',
                'status' => 'active',
                'notes' => 'Proveedor principal de productos electrónicos'
            ],
            [
                'name' => 'Importaciones XYZ Ltda.',
                'email' => 'compras@importacionesxyz.com',
                'phone' => '555-0456',
                'address' => 'Calle Comercio 456, Zona 10, Ciudad',
                'contact_person' => 'María Elena García',
                'tax_id' => '9876543210',
                'status' => 'active',
                'notes' => 'Especialistas en productos importados de calidad'
            ],
            [
                'name' => 'Mayorista Central',
                'email' => 'info@mayoristacentral.com',
                'phone' => '555-0789',
                'address' => 'Boulevard Central 789, Zona 1, Ciudad',
                'contact_person' => 'Roberto Fernández',
                'tax_id' => '5555666677',
                'status' => 'active',
                'notes' => 'Proveedor de productos de consumo masivo'
            ],
            [
                'name' => 'Tecnología Global Inc.',
                'email' => 'contacto@tecglobal.com',
                'phone' => '555-0321',
                'address' => 'Parque Tecnológico 321, Zona 15, Ciudad',
                'contact_person' => 'Ana Sofía López',
                'tax_id' => '1111222233',
                'status' => 'active',
                'notes' => 'Proveedor exclusivo de equipos tecnológicos'
            ],
            [
                'name' => 'Suministros del Norte',
                'email' => 'ventas@suministrosdelnorte.com',
                'phone' => '555-0654',
                'address' => 'Carretera Norte Km 25, Ciudad',
                'contact_person' => 'Carlos Alberto Ruiz',
                'tax_id' => '4444555566',
                'status' => 'inactive',
                'notes' => 'Proveedor temporal - suspendido por revisión'
            ]
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}

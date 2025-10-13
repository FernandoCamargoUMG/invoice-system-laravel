<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tax;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        Tax::create([
            'name' => 'IVA Guatemala',
            'country' => 'GT',
            'rate' => 0.12,
            'included_in_price' => false,
            'applies_to' => 'all',
            'currency' => 'GTQ',
            'active' => true,
        ]);

        Tax::create([
            'name' => 'VAT UK',
            'country' => 'GB',
            'rate' => 0.20,
            'included_in_price' => false,
            'applies_to' => 'sales',
            'currency' => 'GBP',
            'active' => true,
        ]);

        Tax::create([
            'name' => 'GST India',
            'country' => 'IN',
            'rate' => 0.18,
            'included_in_price' => true,
            'applies_to' => 'purchases',
            'currency' => 'INR',
            'active' => true,
        ]);
    }
}

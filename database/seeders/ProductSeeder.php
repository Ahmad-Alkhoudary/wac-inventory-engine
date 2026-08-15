<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds for sample catalog products.
     */
    public function run(): void
    {
        $products = [
            [
                'sku' => 'PROD-WM-001',
                'name' => 'Wireless Ergonomic Mouse',
                'current_stock_qty' => 0,
                'current_total_value' => '0.0000',
            ],
            [
                'sku' => 'PROD-MK-002',
                'name' => 'Mechanical RGB Keyboard',
                'current_stock_qty' => 0,
                'current_total_value' => '0.0000',
            ],
            [
                'sku' => 'PROD-MON-003',
                'name' => '4K USB-C Monitor 27-inch',
                'current_stock_qty' => 0,
                'current_total_value' => '0.0000',
            ],
            [
                'sku' => 'PROD-CHR-004',
                'name' => 'Ergonomic Executive Office Chair',
                'current_stock_qty' => 0,
                'current_total_value' => '0.0000',
            ],
            [
                'sku' => 'PROD-STD-005',
                'name' => 'Aluminum Adjustable Laptop Stand',
                'current_stock_qty' => 0,
                'current_total_value' => '0.0000',
            ],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
        }
    }
}

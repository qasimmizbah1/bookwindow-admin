<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Coupon;

class EcommerceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       ShippingMethod::create([
            'name' => 'Free Shipping',
            'code' => 'free',
            'price' => 0,
            'is_active' => true,
        ]);
        
        ShippingMethod::create([
            'name' => 'Standard Shipping',
            'code' => 'standard',
            'price' => 4.99,
            'is_active' => true,
        ]);
        
        // Coupons
        Coupon::create([
            'code' => 'SAVE10',
            'type' => 'percent',
            'value' => 10,
            'valid_from' => now(),
            'valid_to' => now()->addMonth(),
            'is_active' => true,
        ]);
        
        Coupon::create([
            'code' => 'SAVE20',
            'type' => 'fixed',
            'value' => 20,
            'min_order' => 100,
            'valid_from' => now(),
            'valid_to' => now()->addMonth(),
            'is_active' => true,
        ]);
    }
}

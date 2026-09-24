<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->id();

            // 1. General & Free Shipping Threshold
            $table->boolean('is_free_shipping_enabled')->default(true);
            $table->decimal('min_order_for_free_shipping', 10, 2)->default(999.00);
            $table->decimal('default_flat_shipping', 10, 2)->default(49.00);

            // 2. Weight-Based Shipping Rules
            $table->boolean('is_weight_shipping_enabled')->default(true);
            $table->decimal('packaging_buffer_weight', 8, 3)->default(0.100)->comment('Extra packaging weight in KG');
            $table->json('weight_slabs')->nullable();
            $table->decimal('extra_weight_per_kg_rate', 10, 2)->default(30.00)->comment('Per KG rate if above highest slab');

            // 3. COD (Cash On Delivery) Shipping Slabs
            $table->boolean('is_cod_enabled')->default(true);
            $table->decimal('max_cod_order_amount', 10, 2)->default(5000.00)->comment('Maximum cart value for COD');
            $table->decimal('default_cod_charge', 10, 2)->default(49.00);
            $table->json('cod_slabs')->nullable();

            // Audit
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Seed initial default record with user-specified rules
        DB::table('shipping_settings')->insert([
            'id' => 1,
            'is_free_shipping_enabled' => true,
            'min_order_for_free_shipping' => 999.00,
            'default_flat_shipping' => 49.00,
            'is_weight_shipping_enabled' => true,
            'packaging_buffer_weight' => 0.100,
            'weight_slabs' => json_encode([
                [
                    'label' => 'Up to 500g',
                    'min_weight' => 0.000,
                    'max_weight' => 0.500,
                    'price' => 40.00,
                ],
                [
                    'label' => '500g to 1kg',
                    'min_weight' => 0.501,
                    'max_weight' => 1.000,
                    'price' => 60.00,
                ],
                [
                    'label' => '1kg to 2kg',
                    'min_weight' => 1.001,
                    'max_weight' => 2.000,
                    'price' => 80.00,
                ],
                [
                    'label' => '2kg to 3kg',
                    'min_weight' => 2.001,
                    'max_weight' => 3.000,
                    'price' => 110.00,
                ],
            ]),
            'extra_weight_per_kg_rate' => 30.00,
            'is_cod_enabled' => true,
            'max_cod_order_amount' => 5000.00,
            'default_cod_charge' => 49.00,
            'cod_slabs' => json_encode([
                [
                    'label' => 'Below ₹500',
                    'min_amount' => 0.00,
                    'max_amount' => 499.99,
                    'cod_charge' => 29.00,
                ],
                [
                    'label' => '₹500 to ₹799',
                    'min_amount' => 500.00,
                    'max_amount' => 799.99,
                    'cod_charge' => 49.00,
                ],
                [
                    'label' => '₹800 and above',
                    'min_amount' => 800.00,
                    'max_amount' => 999999.00,
                    'cod_charge' => 79.00,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_settings');
    }
};

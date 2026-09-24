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
        // 1. Ensure updated_by is nullable
        if (Schema::hasColumn('shipping_methods', 'updated_by')) {
            DB::statement('ALTER TABLE `shipping_methods` MODIFY `updated_by` BIGINT UNSIGNED NULL');
        }

        // 2. Add columns if not already added
        Schema::table('shipping_methods', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_methods', 'delivery_time')) {
                $table->string('delivery_time')->nullable()->after('name');
            }
            if (!Schema::hasColumn('shipping_methods', 'description')) {
                $table->string('description')->nullable()->after('delivery_time');
            }
            if (!Schema::hasColumn('shipping_methods', 'calculation_type')) {
                $table->string('calculation_type')->default('weight_based')->after('price');
            }
            if (!Schema::hasColumn('shipping_methods', 'is_free_shipping_eligible')) {
                $table->boolean('is_free_shipping_eligible')->default(true)->after('calculation_type');
            }
            if (!Schema::hasColumn('shipping_methods', 'is_cod_allowed')) {
                $table->boolean('is_cod_allowed')->default(true)->after('is_free_shipping_eligible');
            }
            if (!Schema::hasColumn('shipping_methods', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });

        // 3. Update existing Standard Shipping
        DB::table('shipping_methods')->where('code', 'standard')->update([
            'delivery_time' => '4 - 7 Business Days',
            'description' => 'Standard surface delivery across India (calculated by weight slabs).',
            'calculation_type' => 'weight_based',
            'is_free_shipping_eligible' => true,
            'is_cod_allowed' => true,
            'sort_order' => 1,
        ]);

        // 4. Update or insert Express Shipping
        DB::table('shipping_methods')->updateOrInsert(
            ['code' => 'express'],
            [
                'name' => 'Express Delivery',
                'delivery_time' => '1 - 2 Business Days',
                'description' => 'Priority air shipping with expedited dispatch and delivery.',
                'price' => 99.00,
                'calculation_type' => 'flat_rate',
                'is_free_shipping_eligible' => false,
                'is_cod_allowed' => true,
                'is_active' => true,
                'sort_order' => 2,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_time',
                'description',
                'calculation_type',
                'is_free_shipping_eligible',
                'is_cod_allowed',
                'sort_order',
            ]);
        });
    }
};

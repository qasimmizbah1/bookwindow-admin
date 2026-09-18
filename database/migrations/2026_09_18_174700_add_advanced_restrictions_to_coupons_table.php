<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'payment_method_restriction')) {
                $table->string('payment_method_restriction')->default('all')->after('is_active');
            }
            if (!Schema::hasColumn('coupons', 'max_discount_amount')) {
                $table->decimal('max_discount_amount', 10, 2)->nullable()->after('value');
            }
            if (!Schema::hasColumn('coupons', 'is_first_order_only')) {
                $table->boolean('is_first_order_only')->default(false)->after('user_limit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (Schema::hasColumn('coupons', 'payment_method_restriction')) {
                $table->dropColumn('payment_method_restriction');
            }
            if (Schema::hasColumn('coupons', 'max_discount_amount')) {
                $table->dropColumn('max_discount_amount');
            }
            if (Schema::hasColumn('coupons', 'is_first_order_only')) {
                $table->dropColumn('is_first_order_only');
            }
        });
    }
};

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
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'district')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('district')->nullable()->after('city');
            });
        }

        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'district')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('district')->nullable()->after('city');
            });
        }

        if (Schema::hasTable('shipping_details') && !Schema::hasColumn('shipping_details', 'district')) {
            Schema::table('shipping_details', function (Blueprint $table) {
                $table->string('district')->nullable()->after('city');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'district')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('district');
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'district')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('district');
            });
        }

        if (Schema::hasTable('shipping_details') && Schema::hasColumn('shipping_details', 'district')) {
            Schema::table('shipping_details', function (Blueprint $table) {
                $table->dropColumn('district');
            });
        }
    }
};

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
        Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->foreignId('user_id')->nullable()->constrained();
                $table->decimal('subtotal', 10, 2);
                $table->decimal('shipping_amount', 10, 2);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2);
                $table->string('payment_method')->default('cod');
                $table->string('payment_status')->default('pending');
                $table->string('status')->default('new');
                $table->string('shipping_method')->nullable();
                $table->text('shipping_address')->nullable();
                $table->text('billing_address')->nullable();
                $table->string('customer_email');
                $table->string('customer_phone')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

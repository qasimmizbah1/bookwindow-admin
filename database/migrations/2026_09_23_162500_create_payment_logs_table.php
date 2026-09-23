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
        if (!Schema::hasTable('payment_logs')) {
            Schema::create('payment_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('order_number')->nullable()->index();
                $table->string('gateway', 50)->default('razorpay')->index();
                $table->string('event_type', 50)->index(); // callback, webhook, cron_sync, manual_recovery, order_created
                $table->string('status', 50)->index();     // success, failed, pending, skipped, error
                $table->string('razorpay_order_id')->nullable()->index();
                $table->string('razorpay_payment_id')->nullable()->index();
                $table->decimal('amount', 10, 2)->nullable();
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                // Foreign key constraint with null on delete to avoid cascade accidents
                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};

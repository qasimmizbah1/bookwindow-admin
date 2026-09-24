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
        Schema::table('carts', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->index()->after('session_id');
            $table->string('customer_name', 150)->nullable()->after('status');
            $table->string('customer_email', 150)->nullable()->index()->after('customer_name');
            $table->string('customer_phone', 25)->nullable()->index()->after('customer_email');
            $table->string('recovery_token', 64)->nullable()->unique()->after('customer_phone');
            $table->timestamp('abandoned_at')->nullable()->index()->after('recovery_token');
            $table->timestamp('recovered_at')->nullable()->after('abandoned_at');
            $table->unsignedBigInteger('recovered_order_id')->nullable()->after('recovered_at');
            $table->unsignedInteger('reminder_sent_count')->default(0)->after('recovered_order_id');
            $table->timestamp('last_reminder_at')->nullable()->after('reminder_sent_count');
            $table->string('last_reminder_channel', 25)->nullable()->after('last_reminder_at');
            $table->text('admin_notes')->nullable()->after('last_reminder_channel');

            $table->foreign('recovered_order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['recovered_order_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['customer_email']);
            $table->dropIndex(['customer_phone']);
            $table->dropUnique(['recovery_token']);
            $table->dropIndex(['abandoned_at']);

            $table->dropColumn([
                'status',
                'customer_name',
                'customer_email',
                'customer_phone',
                'recovery_token',
                'abandoned_at',
                'recovered_at',
                'recovered_order_id',
                'reminder_sent_count',
                'last_reminder_at',
                'last_reminder_channel',
                'admin_notes',
            ]);
        });
    }
};

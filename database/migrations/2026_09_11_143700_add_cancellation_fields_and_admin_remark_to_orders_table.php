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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('orders', 'cancelled_by')) {
                $table->string('cancelled_by', 50)->nullable()->after('cancellation_reason');
            }
            if (!Schema::hasColumn('orders', 'admin_remark')) {
                $table->text('admin_remark')->nullable()->after('cancelled_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('orders', 'cancellation_reason')) {
                $columnsToDrop[] = 'cancellation_reason';
            }
            if (Schema::hasColumn('orders', 'cancelled_by')) {
                $columnsToDrop[] = 'cancelled_by';
            }
            if (Schema::hasColumn('orders', 'admin_remark')) {
                $columnsToDrop[] = 'admin_remark';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

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
            $table->string('call_status', 50)->default('pending')->after('status')->index();
            $table->text('call_notes')->nullable()->after('call_status');
            $table->timestamp('called_at')->nullable()->after('call_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['call_status', 'call_notes', 'called_at']);
        });
    }
};

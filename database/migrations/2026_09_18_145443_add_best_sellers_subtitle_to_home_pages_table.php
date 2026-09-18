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
        Schema::table('home_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('home_pages', 'best_sellers_subtitle')) {
                $table->string('best_sellers_subtitle')->nullable()->after('best_sellers_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_pages', function (Blueprint $table) {
            if (Schema::hasColumn('home_pages', 'best_sellers_subtitle')) {
                $table->dropColumn('best_sellers_subtitle');
            }
        });
    }
};

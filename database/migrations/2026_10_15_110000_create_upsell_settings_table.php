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
        Schema::create('upsell_settings', function (Blueprint $table) {
            $table->id();

            // 1. Visibility & Master Controls
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_popup_enabled')->default(true);
            $table->boolean('is_inline_enabled')->default(true);

            // 2. Popup Modal Content
            $table->string('modal_title')->default('Suggested Books You May Also Like');
            $table->string('modal_subtitle')->nullable()->default('Add these recommended books to your order before checkout');

            // 3. Section & Card Content
            $table->string('section_title')->default('Make it perfect');
            $table->string('section_subtitle')->nullable()->default('Customers who bought items in your cart also added these books');
            $table->string('add_button_text')->default('Add');
            $table->unsignedInteger('max_products')->default(8);

            // Audit
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Seed initial default singleton record
        DB::table('upsell_settings')->insert([
            'id' => 1,
            'is_enabled' => true,
            'is_popup_enabled' => true,
            'is_inline_enabled' => true,
            'modal_title' => 'Suggested Books You May Also Like',
            'modal_subtitle' => 'Add these recommended books to your order before checkout',
            'section_title' => 'Make it perfect',
            'section_subtitle' => 'Customers who bought items in your cart also added these books',
            'add_button_text' => 'Add',
            'max_products' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upsell_settings');
    }
};

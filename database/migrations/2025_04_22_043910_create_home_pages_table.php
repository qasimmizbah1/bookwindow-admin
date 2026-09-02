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
        Schema::create('home_pages', function (Blueprint $table) {
            $table->id();
    
    // Slider section
    $table->json('slider_images')->nullable();
    
    // Featured products section
    $table->string('featured_products_title')->nullable();
    $table->json('featured_products')->nullable();
    
    // Best sellers section
    $table->string('best_sellers_title')->nullable();
    $table->json('best_sellers')->nullable();
    
    // Latest products section
    $table->string('latest_products_title')->nullable();
    
    // Categories section
    $table->json('categories')->nullable();
    
    // Custom sections
    $table->json('custom_sections')->nullable();
    
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_pages');
    }
};

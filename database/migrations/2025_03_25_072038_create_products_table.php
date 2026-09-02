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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId(column: 'production_id')
            ->constrained(table: 'productions')
            ->cascadeOnDelete();

            $table->foreignId(column: 'category_id')
            ->constrained(table: 'categories')
            ->cascadeOnDelete();

            $table->string(column: 'name');
            $table->string(column: 'slug')->unique();
            $table->string(column: 'sku')->unique();
            $table->string(column:'image');
            $table->longText(column: 'description')->nullable();
            $table->string(column:'model');
            $table->string(column:'author');
            $table->string(column:'year');
            $table->unsignedBigInteger(column: 'quantity');
            $table->decimal(column: 'mrp', total: 10, places: 2);
            $table->decimal(column: 'price', total: 10, places: 2);
            $table->string(column:'number_of_pages');
            $table->string(column:'book_language');
            $table->string(column:'weight');
            $table->string(column:'isbn');
            $table->string(column:'isbn10');
            $table->string(column:'isbn13');
            $table->boolean(column: 'is_visible')->default(false);
            $table->enum('type', ['Hindi', 'English','Other']);
            $table->date(column:'published_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

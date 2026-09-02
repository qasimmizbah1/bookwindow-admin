<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Add this

class ProductsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $uniqueSlug = $row['slug'] ?? Str::slug($row['name']);

        return Product::updateOrCreate(
            ['slug' => $uniqueSlug],
            [
            'name' => $row['name'],
            'production_id' => $row['production_id'],
            'category_id' => $row['category'] ?? null,
            'sub_category_id' => $row['sub_category_id'] ?? null,
            'child_category_id' => $row['child_category_id'] ?? null,
            'name' => $row['name'] ?? null,
            'slug' => $row['slug'] ?? null,
            'sku' => $row['sku'] ?? null,
            'image' => $row['img'] ?? null,
            'gallery' => $row['gallery'] ?? null,
            'description' => $row['description'] ?? null,
            'meta_tag_title' => $row['meta_title'] ?? null,
            'meta_tag_description' => $row['meta_description'] ?? null,
            'meta_tag_keywords' => $row['meta_keyword'] ?? null,
            'model' => $row['model'] ?? null,
            'author' => $row['author'] ?? null,
            'year' => $row['year'] ?? null,
            'quantity' => $row['quantity'] ?? null,
            'mrp' => $row['mrp'] ?? null,
            'price' => $row['stock_price'] ?? null,
            'number_of_pages' => $row['number_of_pages'] ?? null,
            'book_language' => $row['book_language'] ?? null,
            'weight' => $row['weight'] ?? null,
            'isbn' => $row['isbn'] ?? null,
            'isbn10' => $row['isbn10'] ?? null,
            'isbn10' => $row['isbn10'] ?? null,
            'is_visible' => $row['status'] ?? null,
            'type' => $row['type'] ?? null,
            'published_at' => now(),
           
            // Add other fields as needed
        ]);
    }
}

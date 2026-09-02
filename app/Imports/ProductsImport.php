<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Production;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip empty rows
        if (empty($row['name'])) {
            return null;
        }

        // Generate slug
        $uniqueSlug = $row['slug'] ?? Str::slug(
            $row['name'] . '-' . time() . rand(100,999)
        );


        /*
        |--------------------------------------------------------------------------
        | Production
        |--------------------------------------------------------------------------
        | CSV: publication
        */
        $production = null;

        if (!empty($row['publication'])) {

            $production = Production::firstOrCreate([
                'name' => $row['publication']
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        | CSV: ParentName
        */
        $categoryId = null;

        if (!empty($row['parentname'])) {

            $category = Category::firstOrCreate(
                [
                    'name' => $row['parentname']
                ],
                [
                    'slug' => Str::slug($row['parentname']),
                    'cat_image' => 'default.png'
                ]
            );

            $categoryId = $category->id;
        }


        /*
        |--------------------------------------------------------------------------
        | Sub Category
        |--------------------------------------------------------------------------
        | CSV: SubParentName
        */
        $subcategoryId = null;

        if (!empty($row['subparentname'])) {

            $subcategory = Category::firstOrCreate(
                [
                    'name' => $row['subparentname']
                ],
                [
                    'parent_id' => $categoryId,
                    'slug' => Str::slug($row['subparentname']),
                    'cat_image' => 'default.png'
                ]
            );

            $subcategoryId = $subcategory->id;
        }


        /*
        |--------------------------------------------------------------------------
        | Medium conversion
        |--------------------------------------------------------------------------
        | CSV: medium
        */
        $medium = null;

        if (!empty($row['medium'])) {

            switch ($row['medium']) {

                case 1:
                    $medium = 'English';
                    break;

                case 2:
                    $medium = 'Hindi';
                    break;

                case 3:
                    $medium = 'Other';
                    break;

                default:
                    $medium = null;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Product Save
        |--------------------------------------------------------------------------
        */
        return Product::updateOrCreate(
            [
                'name' => $row['name']
            ],
            [

                'production_id' => $production->id ?? null,

                'category_id' => $categoryId,

                'sub_category_id' => $subcategoryId,

                'child_category_id' => null,


                'name' => $row['name'] ?? null,

                'slug' => $uniqueSlug,

                'sku' => $row['sku'] ?? null,


                'image' => $row['img'] ?? null,

                'gallery' => $row['thumbs'] ?? null,


                'description' => $row['description'] ?? null,


                'meta_tag_title' => $row['meta_title'] ?? null,

                'meta_tag_description' => $row['meta_description'] ?? null,

                'meta_tag_keywords' => $row['meta_keyword'] ?? null,


                'model' => $row['model'] ?? null,

                'author' => $row['author'] ?? null,

                'year' => $row['year'] ?? null,


                'quantity' => $row['quantity'] ?? null,


                'mrp' => $row['raw_price'] ?? null,

                'price' => $row['price'] ?? null,


                'number_of_pages' => $row['pages'] ?? null,


                'book_language' => $row['language'] ?? null,


                'weight' => $row['weight'] ?? null,


                'isbn' => $row['isbn'] ?? null,

                'isbn10' => $row['isbn10'] ?? null,

                'isbn13' => $row['isbn13'] ?? null,


                'is_visible' => $row['status'] ?? 1,


                'type' => $medium,


                'published_at' => !empty($row['date'])
                    ? date('Y-m-d', strtotime($row['date']))
                    : now(),


            ]
        );
    }
}
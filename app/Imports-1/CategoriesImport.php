<?php

namespace App\Imports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Add this

class CategoriesImport implements ToModel, WithHeadingRow 
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $uniqueSlug = $row['slug'] ?? Str::slug($row['name']);
        return Category::updateOrCreate(
            ['slug' => $uniqueSlug],
            [
            'name' => $row['name'],
            'slug' => $row['slug'] ?? Str::slug($row['name']),
            'parent_id' => $row['parent_id'] ?? null,
            'is_visible'=> (int)$row['status'] ?? (int)0,
            'description' => $row['description'] ?? null,
            'meta_tag_title' => $row['meta_title'] ?? null,
            'meta_tag_description' => $row['meta_description'] ?? null,
            'meta_tag_keywords' => $row['meta_keyword'] ?? null,
            
            // Add other fields as needed
        ]);
    }
}

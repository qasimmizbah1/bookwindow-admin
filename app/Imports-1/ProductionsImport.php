<?php

namespace App\Imports;

use App\Models\Production;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Add this

class ProductionsImport implements ToModel, WithHeadingRow 
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Production([
            'name' => $row['name'],
            'is_visible'=> (int)$row['status'] ?? (int)0,
            'description' => $row['description'] ?? null,
            'meta_tag_title' => $row['meta_title'] ?? null,
            'meta_tag_description' => $row['meta_tag_description'] ?? null,
            'meta_tag_keywords' => $row['meta_tag_keywords'] ?? null,
            
            // Add other fields as needed
        ]);
    }
}

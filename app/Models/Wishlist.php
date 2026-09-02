<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'products',
    ];

    protected $casts = [
        'products' => 'array',
    ];
     protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
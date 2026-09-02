<?php
// app/Models/ProductSold.php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProductSold extends Model
{
    protected $table = 'productsold'; // if your table name is different
    
    protected $fillable = [
        
        'name','brands_id','slug','sku','image', 'description','quantity','price','published_at,
    ];
    
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }
}
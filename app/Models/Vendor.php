<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
            protected $fillable = [
            'vendor_name',
            'user_id',
            'vendor_logo',        
            'isbn_number',       
            'vendor_phone',     
            'vendor_address',     
            'vendor_website',    

            ];

            public function user()
            {
            return $this->belongsTo(User::class);
            }

            public function products()
            {
            return $this->hasMany(Product::class);
            }

            public function orders()
            {
            return $this->belongsToMany(Order::class, 'order_items')
            ->using(OrderItem::class)
            ->withPivot(['quantity', 'price']);
            }

            
            
}

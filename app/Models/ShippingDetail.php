<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingDetail extends Model
{
    protected $fillable = [
        'order_id', 'first_name', 'last_name', 'email', 'phone',
        'address', 'city', 'state', 'country', 'postal_code'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

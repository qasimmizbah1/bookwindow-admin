<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Order extends Model
{
    //
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'email',
        'first_name',
        'last_name',
        'user_id',
        'session_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'delivery_amount',
        'total_amount',
        'payment_method',
        'razorpay_order_id',
        'razorpay_payment_id',
        'payment_status',
        'status',
        'shipping_method',
        'address',
        'address_2',
        'coupon_code',
        'customer_phone',
        'notes',
        'zip_code',
        'city',
        'state',
        'country',
        'tracking_id'
        
    ];
    

    public function customer(): BelongsTo
    {
        return $this->belongsTo(related: Customer::class);
    }
    public function customername()
    {
    return $this->belongsTo(Customer::class, 'user_id');
    }
    public function items(): HasMany
    {
        return $this->hasMany(related: OrderItem::class);
    }
    public function shippingDetail()
    {
        return $this->hasOne(ShippingDetail::class);
    }
    public function getFullNameAttribute()
    {
    return $this->first_name . ' ' . $this->last_name;
    }
    public function product()
    {
    return $this->belongsTo(Product::class);
    }
    public function vendor()
    {
    return $this->belongsTo(Vendor::class);
    }

    public function vendorItems()
    {
    return $this->items()->where('vendor_id', $vendorId);
    }
    
    


  

   

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'vendor_name',
        'user_id',
        'contact_person',
        'vendor_logo',
        'isbn_number',
        'pan_number',
        'gst_number',
        'vendor_phone',
        'vendor_address',
        'city',
        'state',
        'pincode',
        'vendor_website',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'commission_percentage',
        'approval_status',
    ];

    protected $casts = [
        'commission_percentage' => 'float',
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

    /**
     * Get global platform commission percentage (managed centrally via Settings)
     */
    public function getCommissionRateAttribute(): float
    {
        return Setting::getVendorCommission();
    }

    /**
     * Calculate platform commission fee for given gross amount
     */
    public function calculateCommissionFee(float $grossAmount): float
    {
        return round($grossAmount * ($this->commission_rate / 100), 2);
    }

    /**
     * Calculate vendor net payout earnings for given gross amount
     */
    public function calculateVendorPayout(float $grossAmount): float
    {
        return round($grossAmount - $this->calculateCommissionFee($grossAmount), 2);
    }
}

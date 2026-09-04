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
     * Get vendor platform commission percentage (per-vendor custom rate if set, else global setting)
     */
    public function getCommissionRateAttribute(): float
    {
        if (isset($this->attributes['commission_percentage']) && is_numeric($this->attributes['commission_percentage']) && (float) $this->attributes['commission_percentage'] > 0) {
            return (float) $this->attributes['commission_percentage'];
        }

        return Setting::getVendorCommission();
    }

    /**
     * Get GST rate percentage applicable on the platform commission fee (default 18.00%)
     */
    public function getCommissionGstRateAttribute(): float
    {
        return Setting::getCommissionGst();
    }

    /**
     * Calculate platform commission fee for given gross amount (before GST)
     */
    public function calculateCommissionFee(float $grossAmount): float
    {
        return round($grossAmount * ($this->commission_rate / 100), 2);
    }

    /**
     * Calculate 18% GST on the platform commission fee
     */
    public function calculateCommissionGst(float $grossAmount): float
    {
        $fee = $this->calculateCommissionFee($grossAmount);
        return round($fee * ($this->commission_gst_rate / 100), 2);
    }

    /**
     * Calculate total platform deduction (Commission Fee + 18% GST on Fee)
     */
    public function calculateTotalDeduction(float $grossAmount): float
    {
        return round($this->calculateCommissionFee($grossAmount) + $this->calculateCommissionGst($grossAmount), 2);
    }

    /**
     * Calculate vendor net payout earnings (Gross Amount - Total Deduction)
     */
    public function calculateVendorPayout(float $grossAmount): float
    {
        return round($grossAmount - $this->calculateTotalDeduction($grossAmount), 2);
    }
}

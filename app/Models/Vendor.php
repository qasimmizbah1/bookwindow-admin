<?php

namespace App\Models;

use App\Mail\VendorApprovedMail;
use App\Mail\VendorSuspendedMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class Vendor extends Model
{
    protected static function booted(): void
    {
        static::updated(function (Vendor $vendor) {
            if ($vendor->wasChanged('approval_status')) {
                $oldStatus = $vendor->getOriginal('approval_status');
                $newStatus = $vendor->approval_status;

                $user = $vendor->user ?? User::find($vendor->user_id);

                if ($user && !empty($user->email)) {
                    if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                        try {
                            Mail::to($user->email)->send(new VendorApprovedMail($user, $vendor));
                        } catch (\Exception $e) {
                            logger()->error('Failed to send VendorApprovedMail: ' . $e->getMessage(), [
                                'vendor_id' => $vendor->id,
                                'user_id' => $user->id,
                            ]);
                        }
                    } elseif ($newStatus === 'suspended' && $oldStatus !== 'suspended') {
                        try {
                            Mail::to($user->email)->send(new VendorSuspendedMail($user, $vendor));
                        } catch (\Exception $e) {
                            logger()->error('Failed to send VendorSuspendedMail: ' . $e->getMessage(), [
                                'vendor_id' => $vendor->id,
                                'user_id' => $user->id,
                            ]);
                        }
                    }
                }
            }
        });
    }
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

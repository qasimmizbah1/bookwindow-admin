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
        'call_status',
        'call_notes',
        'called_at',
        'shipping_method',
        'address',
        'address_2',
        'coupon_code',
        'customer_phone',
        'notes',
        'cancellation_reason',
        'cancelled_by',
        'admin_remark',
        'zip_code',
        'city',
        'district',
        'state',
        'country',
        'tracking_id',
        'courier_partner',
    ];

    protected $casts = [
        'called_at' => 'datetime',
    ];
    

    public const CALL_STATUS_PENDING = 'pending';
    public const CALL_STATUS_CONFIRMED = 'confirmed';
    public const CALL_STATUS_NOT_PICKED = 'not_picked';
    public const CALL_STATUS_CALL_AGAIN = 'call_again';
    public const CALL_STATUS_CANCELLED = 'cancelled_by_customer';
    public const CALL_STATUS_WRONG_NUMBER = 'wrong_number';

    public static function getCallStatuses(): array
    {
        return [
            self::CALL_STATUS_PENDING => 'To Call / Pending',
            self::CALL_STATUS_CONFIRMED => 'Confirmed on Call',
            self::CALL_STATUS_NOT_PICKED => 'Not Picked',
            self::CALL_STATUS_CALL_AGAIN => 'Call Again / Busy',
            self::CALL_STATUS_CANCELLED => 'Cancelled on Call',
            self::CALL_STATUS_WRONG_NUMBER => 'Wrong / Invalid Number',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(related: Customer::class);
    }
    public function customername()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function recoveredCarts(): HasMany
    {
        return $this->hasMany(Cart::class, 'recovered_order_id');
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

    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class)->latest();
    }

    /**
     * Normalize and format any phone number to standard +91 XXXXXXXXXX format.
     */
    public static function formatPhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return 'N/A';
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '91') && strlen($digits) > 10) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) > 10) {
            $digits = substr($digits, 1);
        }
        $digits = substr($digits, -10);

        return !empty($digits) ? '+91 ' . $digits : $phone;
    }

    /**
     * Accessor for formatted phone number with +91 prefix.
     */
    public function getFormattedPhoneAttribute(): string
    {
        return self::formatPhoneNumber($this->customer_phone);
    }
}

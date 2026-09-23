<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_number',
        'gateway',
        'event_type',
        'status',
        'razorpay_order_id',
        'razorpay_payment_id',
        'amount',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

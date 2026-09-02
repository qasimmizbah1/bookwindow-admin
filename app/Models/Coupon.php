<?php
// app/Models/Coupon.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    // protected $fillable = [
    //     'code', 'type', 'value', 'min_cart_amount', 
    //     'valid_from', 'valid_to', 'is_active'
    // ];
    
    protected $fillable = [
        'code',
        'type',
        'value',
        'catgeory_id',
        'min_cart_amount',
        'max_cart_amount',
        'usage_limit',
        'user_limit',
        'valid_from',
        'valid_to',
        'is_active',
        'exclude_categories',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'catgeory_id'=> 'array',
    ];

    /**
     * Scope to get only valid coupons
     */
    public function scopeValid(Builder $query): Builder
    {
        $now = Carbon::now();
        
        return $query->where('is_active', true)
            ->where('valid_from', '<=', $now)
            ->where('valid_to', '>=', $now);
    }

    /**
     * Calculate discount amount based on cart total
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'fixed') {
            return min($this->value, $amount);
        }
        
        // For percentage discount
        return round($amount * ($this->value / 100), 2);
    }

    /**
     * Check if coupon is valid for a given amount
     */
    public function isValidForAmount(float $amount): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();
        if ($now->lt($this->valid_from) || $now->gt($this->valid_to)) {
            return false;
        }

        if ($this->min_cart_amount && $amount < $this->min_cart_amount) {
            return false;
        }

        return true;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(related: Category::class);

    }
    protected static function booted(): void
    {
        static::updating(function ($home) {
            if (auth()->check()) {
                $home->updated_by = auth()->id();
            }
        });
        static::creating(function ($post) {
            $post->updated_by = auth()->id();
        });
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'name',
        'slug',
        'sub_title',
        'sku',
        'category_id',
        'sub_category_id',
        'child_category_id',
        'description',
        'meta_tag_title',
        'meta_tag_description',
        'meta_tag_keywords',
        'image',
        'gallery',
        'model',
        'author',
        'year',
        'mrp',
        'number_of_pages',
        'book_language',
        'weight',
        'isbn',
        'isbn10',
        'isbn13',
        'quantity',
        'price',
        'is_visible',
        'type',
        'updated_by',
        'published_at',
        'vendor_id'  // ✅ Vendor ID field exists
    ];

    protected $casts = [
        'gallery' => 'array',
        'category_id' => 'array',
        'is_visible' => 'boolean',  
        'published_at' => 'datetime', 
    ];

    protected static function boot()
    {
        parent::boot();
        // Your existing boot logic
    }

    // Vendor Relationship - Already exists ✅
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    //  User (Admin) who updated the product
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    //  Production (Publication) Relationship
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    //  Category Relationship (Single category)
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Categories Relationship (Multiple categories)
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    // Order Items Relationship
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scope: Get products by vendor
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    //Scope: Get admin products (no vendor)
    public function scopeAdminProducts($query)
    {
        return $query->whereNull('vendor_id');
    }

    //Scope: Get vendor products
    public function scopeVendorProducts($query)
    {
        return $query->whereNotNull('vendor_id');
    }

    //Scope: Get active products
    public function scopeActive($query)
    {
        return $query->where('is_visible', 1);
    }

    //Helper: Check if product has vendor
    public function hasVendor(): bool
    {
        return !is_null($this->vendor_id);
    }

    //Helper: Get vendor name or "Admin Product"
    public function getVendorNameAttribute(): string
    {
        if ($this->vendor) {
            return $this->vendor->vendor_name;
        }
        return 'Admin Product 🏪';
    }

    //Helper: Check if product is active
    public function isActive(): bool
    {
        return $this->is_visible == 1;
    }

    // Boot Method - Auto set updated_by
    protected static function booted(): void
    {
        static::updating(function ($product) {
            if (auth()->check()) {
                $product->updated_by = auth()->id();
            }
        });

        static::creating(function ($product) {
            if (auth()->check()) {
                $product->updated_by = auth()->id();
            }
        });
    }
}
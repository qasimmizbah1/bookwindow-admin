<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class UpsellSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_popup_enabled' => 'boolean',
        'is_inline_enabled' => 'boolean',
        'max_products' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function ($setting) {
            if (auth()->check()) {
                $setting->updated_by = auth()->id();
            }
        });

        static::saved(function () {
            Cache::forget('upsell_global_settings');
        });

        static::deleted(function () {
            Cache::forget('upsell_global_settings');
        });
    }

    /**
     * Singleton accessor for upsell settings with persistent caching
     */
    public static function current(): self
    {
        return Cache::rememberForever('upsell_global_settings', function () {
            return static::firstOrCreate(
                ['id' => 1],
                [
                    'is_enabled' => true,
                    'is_popup_enabled' => true,
                    'is_inline_enabled' => true,
                    'modal_title' => 'Suggested Books You May Also Like',
                    'modal_subtitle' => 'Add these recommended books to your order before checkout',
                    'section_title' => 'Make it perfect',
                    'section_subtitle' => 'Customers who bought items in your cart also added these books',
                    'add_button_text' => 'Add',
                    'max_products' => 8,
                ]
            );
        });
    }

    /**
     * Format into standard array structure expected by API/frontend
     */
    public function toConfigArray(): array
    {
        return [
            'enabled' => (bool) $this->is_enabled,
            'popup_enabled' => (bool) $this->is_popup_enabled,
            'inline_enabled' => (bool) $this->is_inline_enabled,
            'modal_title' => (string) ($this->modal_title ?: 'Suggested Books You May Also Like'),
            'modal_subtitle' => (string) ($this->modal_subtitle ?: ''),
            'section_title' => (string) ($this->section_title ?: 'Make it perfect'),
            'section_subtitle' => (string) ($this->section_subtitle ?: ''),
            'add_button_text' => (string) ($this->add_button_text ?: 'Add'),
            'max_products' => (int) ($this->max_products ?: 8),
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

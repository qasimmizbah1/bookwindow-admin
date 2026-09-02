<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Get a setting value by key with fallback default
     */
    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever("app_setting_{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set / Update a setting value by key
     */
    public static function set(string $key, $value, string $group = 'general'): self
    {
        Cache::forget("app_setting_{$key}");

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * Get the global vendor commission rate percentage (default 7.00%)
     */
    public static function getVendorCommission(): float
    {
        $val = static::get('vendor_commission_percentage', '7.00');
        return is_numeric($val) ? (float) $val : 7.00;
    }
}

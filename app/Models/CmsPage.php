<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CmsPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'banner_images',
        'short_description',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_active',
        'updated_at',
        'updated_by',
    ];
    
    
    protected static function booted(): void
    {
        static::updating(function ($category) {
            if (auth()->check()) {
                $category->updated_by = auth()->id();
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

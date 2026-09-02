<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 
        'slug',
        'publication_img',
        'is_visible', 
        'description',
        'meta_tag_title',
        'meta_tag_description',
        'meta_tag_keywords'
    ];

    public function productions(): HasMany
    {
        return $this->hasmany(related: Product::class);
    }
    public function products(): HasMany
    {
        return $this->hasmany(related: Product::class);
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

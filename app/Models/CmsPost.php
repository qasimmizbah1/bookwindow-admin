<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CmsPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'cms_category_id', 'title', 'slug', 'content', 'is_active', 'image', 'meta_title', 'meta_description', 'meta_keywords', 'updated_at'
    ];

    public function category()
    {
        return $this->belongsTo(CmsCategory::class, 'cms_category_id');
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

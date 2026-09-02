<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CmsCategory extends Model
{
    use HasFactory;

     protected $fillable = ['name', 'slug', 'content','updated_at', 'updated_by'];
    

    public function posts()
    {
        return $this->hasMany(CmsPost::class);
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

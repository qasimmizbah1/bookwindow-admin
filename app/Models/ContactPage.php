<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactPage extends Model
{
    protected $fillable = [
        'con_title',
        'con_address',
        'con_phone',
        'con_email',
        'con_map',
        'updated_at',
        'updated_by',
        
    ];

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

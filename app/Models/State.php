<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'is_active'
    ];

    
    public function cities()
    {
    return $this->hasMany(City::class)->where('is_active', true);
    }

}
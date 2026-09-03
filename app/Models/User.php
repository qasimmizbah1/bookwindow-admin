<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }



    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }
   
        public function vendor()
        {
        return $this->hasOne(\App\Models\Vendor::class);
        }

       

        public function isAdmin()
        {
        return $this->role === 'admin';
        }

        public function isVendor()
        {
        return $this->role === 'vendor';
        }

        // Relationship to vendor profile if you still want separate vendor details
        public function vendorProfile()
        {
        return $this->hasOne(Vendor::class);
        }
         public function getVendorNameAttribute()
    {
        return $this->vendor?->name ?? 'N/A';
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // 1. Account must be active
        if (! $this->is_active) {
            return false;
        }

        // 2. Admin can access if active
        if ($this->isAdmin()) {
            return true;
        }

        // 3. Vendor can only access if active AND approved
        if ($this->isVendor()) {
            return $this->vendor && $this->vendor->approval_status === 'approved';
        }

        return false;
    }

    /**
     * Helper to check if vendor is fully approved and active.
     */
    public function isApprovedVendor(): bool
    {
        return $this->isVendor() && $this->is_active && $this->vendor?->approval_status === 'approved';
    }
}

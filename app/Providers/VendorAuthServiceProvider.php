<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class VendorUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        return \App\Models\User::with('vendor')->where('id', $identifier)->first();
    }

    public function retrieveByToken($identifier, $token) { /* ... */ }
    public function updateRememberToken(Authenticatable $user, $token) { /* ... */ }
    
    public function retrieveByCredentials(array $credentials)
    {
        $user = \App\Models\User::where('email', $credentials['email'])->first();
        
        if ($user && $user->vendor) {
            return $user;
        }
        
        return null;
    }
    
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return Hash::check($credentials['password'], $user->getAuthPassword());
    }
}
<?php

// app/Observers/UserObserver.php

namespace App\Observers;

use App\Models\User;
use App\Models\Vendor;

class UserObserver
{
    public function created(User $user)
    {
        // No longer needed - handled in CreateUser page
    }

    public function updated(User $user)
    {
        // No longer needed - handled in EditUser page
    }

    public function deleting(User $user)
    {
        if ($user->vendor) {
            $user->vendor->delete();
        }
    }
}
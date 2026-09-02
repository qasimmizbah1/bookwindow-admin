<?php

namespace App\Policies;

use App\Models\User;

class MenuItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, mixed $menuItem = null): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, mixed $menuItem = null): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, mixed $menuItem = null): bool
    {
        return $user->isAdmin();
    }
}

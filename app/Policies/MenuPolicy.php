<?php

namespace App\Policies;

use App\Models\User;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, mixed $menu = null): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, mixed $menu = null): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, mixed $menu = null): bool
    {
        return $user->isAdmin();
    }
}

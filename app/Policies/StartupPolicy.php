<?php



namespace App\Policies;

use App\Models\Startup;
use App\Models\User;

class StartupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Startup $startup): bool
    {
        return $startup->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Startup $startup): bool
    {
        return $startup->user_id === $user->id;
    }

    public function delete(User $user, Startup $startup): bool
    {
        return $startup->user_id === $user->id;
    }
}

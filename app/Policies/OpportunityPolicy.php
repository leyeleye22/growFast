<?php



namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_opportunities');
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        if ($opportunity->subscription_required_id) {
            $activeSub = $user->userSubscriptions()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->where('subscription_id', $opportunity->subscription_required_id)
                ->exists();

            return $activeSub;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_opportunities');
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        return $user->can('manage_opportunities');
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        return $user->can('manage_opportunities');
    }
}

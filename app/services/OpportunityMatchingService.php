<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\Startup;
use Illuminate\Support\Collection;

class OpportunityMatchingService
{
    protected int $stageWeight = 25;

    protected int $industryWeight = 25;

    protected int $countryWeight = 15;

    protected int $fundingWeight = 10;

    protected int $subscriptionWeight = 5;

    protected int $revenueWeight = 5;

    protected int $ownershipWeight = 5;

    public function calculateMatches(Startup $startup): Collection
    {
        $opportunities = Opportunity::active()
            ->notExpired()
            ->get();

        $results = collect();

        foreach ($opportunities as $opportunity) {
            $breakdown = [];
            $score = 0;

            $stageMatch = $this->matchStage($startup, $opportunity);
            $score += $stageMatch * $this->stageWeight;
            $breakdown['stage'] = $stageMatch * $this->stageWeight;

            $industryMatch = $this->matchIndustry($startup, $opportunity);
            $score += $industryMatch * $this->industryWeight;
            $breakdown['industry'] = $industryMatch * $this->industryWeight;

            $countryMatch = $this->matchCountry($startup, $opportunity);
            $score += $countryMatch * $this->countryWeight;
            $breakdown['country'] = $countryMatch * $this->countryWeight;

            $fundingMatch = $this->matchFunding($startup, $opportunity);
            $score += $fundingMatch * $this->fundingWeight;
            $breakdown['funding'] = $fundingMatch * $this->fundingWeight;

            $subscriptionMatch = $this->matchSubscription($startup, $opportunity);
            $score += $subscriptionMatch * $this->subscriptionWeight;
            $breakdown['subscription'] = $subscriptionMatch * $this->subscriptionWeight;

            $revenueMatch = $this->matchRevenue($startup, $opportunity);
            $score += $revenueMatch * $this->revenueWeight;
            $breakdown['revenue'] = $revenueMatch * $this->revenueWeight;

            $ownershipMatch = $this->matchOwnership($startup, $opportunity);
            $score += $ownershipMatch * $this->ownershipWeight;
            $breakdown['ownership'] = $ownershipMatch * $this->ownershipWeight;

            OpportunityMatch::updateOrCreate(
                [
                    'startup_id' => $startup->id,
                    'opportunity_id' => $opportunity->id,
                ],
                [
                    'score' => $score,
                    'score_breakdown' => $breakdown,
                ]
            );

            $results->push([
                'opportunity' => $opportunity,
                'score' => $score,
                'breakdown' => $breakdown,
            ]);
        }

        return $results->sortByDesc('score')->values();
    }

    protected function matchStage(Startup $startup, Opportunity $opportunity): float
    {
        if ($startup->stage && $opportunity->stages->isNotEmpty()) {
            $match = $opportunity->stages->contains('slug', str($startup->stage)->slug());

            return $match ? 1.0 : 0.0;
        }

        return 0.5;
    }

    protected function matchIndustry(Startup $startup, Opportunity $opportunity): float
    {
        if ($startup->industry && $opportunity->industries->isNotEmpty()) {
            $match = $opportunity->industries->contains('slug', str($startup->industry)->slug());

            return $match ? 1.0 : 0.0;
        }

        return 0.5;
    }

    protected function matchCountry(Startup $startup, Opportunity $opportunity): float
    {
        if (!$startup->country) {
            return 0.5;
        }

        $codes = $opportunity->countryCodes->pluck('country_code')->map(fn ($c) => strtoupper($c));

        return $codes->contains(strtoupper($startup->country)) ? 1.0 : 0.0;
    }

    protected function matchFunding(Startup $startup, Opportunity $opportunity): float
    {
        if (!$opportunity->funding_min && !$opportunity->funding_max) {
            return 0.5;
        }

        return 0.5;
    }

    protected function matchRevenue(Startup $startup, Opportunity $opportunity): float
    {
        $criteria = $opportunity->eligibility_criteria ?? [];
        $revMin = $criteria['revenue_min'] ?? null;
        $revMax = $criteria['revenue_max'] ?? null;
        if (!$revMin && !$revMax) {
            return 0.5;
        }
        $startupRev = $startup->revenue_min ?? $startup->revenue_max ?? null;
        if (!$startupRev) {
            return 0.5;
        }
        if ($revMin && $startupRev < (float) $revMin) {
            return 0.0;
        }
        if ($revMax && $startupRev > (float) $revMax) {
            return 0.0;
        }

        return 1.0;
    }

    protected function matchOwnership(Startup $startup, Opportunity $opportunity): float
    {
        $criteria = $opportunity->eligibility_criteria ?? [];
        $required = $criteria['ownership'] ?? [];
        if (empty($required) || !is_array($required)) {
            return 0.5;
        }
        if (!$startup->ownership_type) {
            return 0.5;
        }

        return in_array(strtolower($startup->ownership_type), array_map('strtolower', $required)) ? 1.0 : 0.0;
    }

    protected function matchSubscription(Startup $startup, Opportunity $opportunity): float
    {
        if (!$opportunity->subscription_required_id) {
            return 1.0;
        }

        $user = $startup->user;
        $activeSub = $user->userSubscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->whereHas('subscription', fn ($q) => $q->where('id', $opportunity->subscription_required_id))
            ->exists();

        return $activeSub ? 1.0 : 0.0;
    }

    public function recalculateAll(): void
    {
        Startup::each(fn (Startup $startup) => $this->calculateMatches($startup));
    }
}

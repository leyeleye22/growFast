<?php



namespace App\Services;

use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\Startup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
            ->with(['industries', 'stages', 'countryCodes'])
            ->get();

        $opportunities = $this->filterByStartupCriteria($startup, $opportunities);

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

        if ($startup->funding_min !== null || $startup->funding_max !== null) {
            $oppMin = $opportunity->funding_min ? (float) $opportunity->funding_min : 0.0;
            $oppMax = $opportunity->funding_max ? (float) $opportunity->funding_max : PHP_FLOAT_MAX;
            $startupMin = $startup->funding_min ? (float) $startup->funding_min : 0.0;
            $startupMax = $startup->funding_max ? (float) $startup->funding_max : PHP_FLOAT_MAX;
            $overlaps = $oppMin <= $startupMax && $oppMax >= $startupMin;

            return $overlaps ? 1.0 : 0.0;
        }

        return 0.5;
    }

    /**
     * Filter opportunities by startup's opportunity criteria (funding, industries, stages, countries, deadline).
     * Only applies filters when startup has defined them; null/empty = no filter.
     */
    protected function filterByStartupCriteria(Startup $startup, Collection $opportunities): Collection
    {
        return $opportunities->filter(function (Opportunity $opp) use ($startup): bool {
            if ($startup->funding_min !== null && $opp->funding_max !== null && (float) $opp->funding_max < (float) $startup->funding_min) {
                return false;
            }
            if ($startup->funding_max !== null && $opp->funding_min !== null && (float) $opp->funding_min > (float) $startup->funding_max) {
                return false;
            }

            $fundingTypes = $startup->funding_types ?? [];
            if (!empty($fundingTypes) && $opp->funding_type !== null) {
                $normalized = array_map('strtolower', $fundingTypes);
                if (!in_array(strtolower($opp->funding_type), $normalized)) {
                    return false;
                }
            }

            $preferredIndustries = $startup->preferred_industries ?? [];
            if (!empty($preferredIndustries) && $opp->industries->isNotEmpty()) {
                $oppSlugs = $opp->industries->pluck('slug')->map(fn ($s) => strtolower($s))->toArray();
                $match = collect($preferredIndustries)->contains(fn ($p) => in_array(Str::slug($p), $oppSlugs) || in_array(strtolower($p), $oppSlugs));
                if (!$match) {
                    return false;
                }
            }

            $preferredStages = $startup->preferred_stages ?? [];
            if (!empty($preferredStages) && $opp->stages->isNotEmpty()) {
                $oppSlugs = $opp->stages->pluck('slug')->map(fn ($s) => strtolower($s))->toArray();
                $match = collect($preferredStages)->contains(fn ($p) => in_array(Str::slug($p), $oppSlugs) || in_array(strtolower($p), $oppSlugs));
                if (!$match) {
                    return false;
                }
            }

            $preferredCountries = $startup->preferred_countries ?? [];
            if (!empty($preferredCountries) && $opp->countryCodes->isNotEmpty()) {
                $oppCodes = $opp->countryCodes->pluck('country_code')->map(fn ($c) => strtoupper($c))->toArray();
                $match = collect($preferredCountries)->contains(fn ($p) => in_array(strtoupper($p), $oppCodes));
                if (!$match) {
                    return false;
                }
            }

            if ($startup->deadline_min !== null && $opp->deadline !== null && $opp->deadline->lt($startup->deadline_min)) {
                return false;
            }
            if ($startup->deadline_max !== null && $opp->deadline !== null && $opp->deadline->gt($startup->deadline_max)) {
                return false;
            }

            return true;
        })->values();
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

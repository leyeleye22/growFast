<?php

namespace App\services\Scraping;

use App\Models\Industry;
use App\Models\Stage;
use Illuminate\Support\Str;

/**
 * Maps extracted opportunity data to database schema.
 * Resolves industry, stage, country to existing slugs.
 */
class OpportunityDataMapper
{
    /**
     * Map extracted data to Opportunity fillable attributes.
     *
     * @param  array<string, mixed>  $extracted
     * @return array<string, mixed>
     */
    public function toOpportunityAttributes(array $extracted): array
    {
        return [
            'title' => $this->sanitizeTitle($extracted['title'] ?? null),
            'description' => $this->sanitizeDescription($extracted['description'] ?? null),
            'funding_type' => $this->normalizeFundingType($extracted['funding_type'] ?? null),
            'deadline' => $extracted['deadline'] ?? null,
            'funding_min' => $this->castNumeric($extracted['funding_min'] ?? null),
            'funding_max' => $this->castNumeric($extracted['funding_max'] ?? null),
            'external_url' => $extracted['external_url'] ?? null,
            'source' => $extracted['source'] ?? 'scraper',
            'eligibility_criteria' => $this->normalizeEligibility($extracted['eligibility_criteria'] ?? null),
        ];
    }

    /**
     * Resolve industry slug to industry IDs for sync.
     *
     * @return array<int>
     */
    public function resolveIndustryIds(?string $industrySlug): array
    {
        if (! $industrySlug || trim($industrySlug) === '') {
            return [];
        }
        $slug = Str::slug($industrySlug);
        $industry = Industry::where('slug', $slug)->first();
        return $industry ? [$industry->id] : [];
    }

    /**
     * Resolve stage slug to stage IDs for sync.
     *
     * @return array<int>
     */
    public function resolveStageIds(?string $stageSlug): array
    {
        if (! $stageSlug || trim($stageSlug) === '') {
            return [];
        }
        $slug = Str::slug($stageSlug);
        $stage = Stage::where('slug', $slug)->first();
        return $stage ? [$stage->id] : [];
    }

    /**
     * Normalize country to ISO 3166-1 alpha-2 code.
     *
     * @return array<string>
     */
    public function resolveCountryCodes(mixed $country): array
    {
        if ($country === null) {
            return [];
        }
        if (is_array($country)) {
            return array_values(array_filter(array_map([$this, 'normalizeCountryCode'], $country)));
        }
        $code = $this->normalizeCountryCode($country);
        return $code ? [$code] : [];
    }

    protected function normalizeCountryCode(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }
        $str = strtoupper(trim((string) $value));
        if (strlen($str) === 2) {
            return $str;
        }
        $map = [
            'ghana' => 'GH', 'senegal' => 'SN', 'nigeria' => 'NG', 'france' => 'FR',
            'usa' => 'US', 'united states' => 'US', 'uk' => 'GB', 'united kingdom' => 'GB',
        ];
        return $map[strtolower($str)] ?? null;
    }

    protected function sanitizeTitle(?string $value): string
    {
        if (! $value) {
            return 'Untitled';
        }
        $t = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = strip_tags($t);
        return trim(mb_substr($t, 0, 255)) ?: 'Untitled';
    }

    protected function sanitizeDescription(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $t = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = strip_tags($t);
        $t = preg_replace('/\s+/', ' ', trim($t));
        return $t !== '' ? $t : null;
    }

    protected function normalizeFundingType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $valid = ['grant', 'equity', 'debt', 'prize', 'other'];
        $lower = strtolower(trim($value));
        if (in_array($lower, $valid, true)) {
            return $lower;
        }
        if (str_contains($lower, 'grant')) {
            return 'grant';
        }
        if (str_contains($lower, 'equity')) {
            return 'equity';
        }
        if (str_contains($lower, 'debt')) {
            return 'debt';
        }
        if (str_contains($lower, 'prize') || str_contains($lower, 'hackathon')) {
            return 'prize';
        }
        return 'other';
    }

    protected function castNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        return null;
    }

    protected function normalizeEligibility(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }
        if (is_string($value)) {
            $items = preg_split('/[\n,;]+/', $value);
            return array_values(array_filter(array_map('trim', $items)));
        }
        return null;
    }
}

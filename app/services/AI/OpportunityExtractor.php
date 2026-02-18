<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\GeminiService;
use Carbon\Carbon;

class OpportunityExtractor
{
    public function __construct(
        protected GeminiService $geminiService
    ) {}

    public function extract(string $rawContent): ?array
    {
        $extracted = $this->callAI($rawContent);

        if (!$extracted) {
            return null;
        }

        $deadline = $extracted['deadline'] ?? null;
        if ($deadline && Carbon::parse($deadline)->lt(now()->startOfDay())) {
            return null;
        }

        return $this->normalizeExtracted($extracted);
    }

    /**
     * Extract opportunity data for admin testing (no deadline filter).
     */
    public function extractForTest(string $rawContent): array
    {
        $extracted = $this->callAI($rawContent);

        if (!$extracted) {
            return [
                'title' => null,
                'funding_type' => null,
                'deadline' => null,
                'industry' => null,
                'stage' => null,
                'funding_min' => null,
                'funding_max' => null,
                'source' => 'none',
            ];
        }

        return array_merge($this->normalizeExtracted($extracted), [
            'source' => $this->geminiService->isConfigured() ? 'gemini' : 'regex',
        ]);
    }

    protected function normalizeExtracted(array $extracted): array
    {
        $deadline = $extracted['deadline'] ?? null;

        return [
            'title' => $extracted['title'] ?? 'Untitled',
            'funding_type' => $this->normalizeFundingType($extracted['funding_type'] ?? null),
            'deadline' => $deadline,
            'industry' => $extracted['industry'] ?? null,
            'stage' => $extracted['stage'] ?? null,
            'funding_min' => $this->parseFundingAmount($extracted['funding_min'] ?? null),
            'funding_max' => $this->parseFundingAmount($extracted['funding_max'] ?? null),
        ];
    }

    protected function callAI(string $rawContent): ?array
    {
        if ($this->geminiService->isConfigured()) {
            $result = $this->geminiService->extractOpportunityFromContent($rawContent);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->extractWithRegex($rawContent);
    }

    protected function extractWithRegex(string $content): array
    {
        return [
            'title' => $this->extractTitle($content),
            'funding_type' => $this->extractFundingType($content),
            'deadline' => $this->extractDeadline($content),
            'industry' => null,
            'stage' => null,
            'funding_min' => $this->extractFundingMin($content),
            'funding_max' => $this->extractFundingMax($content),
        ];
    }

    protected function extractTitle(string $content): string
    {
        preg_match('/<title[^>]*>([^<]+)<\/title>/i', $content, $m);
        return trim($m[1] ?? substr(strip_tags($content), 0, 100));
    }

    protected function extractFundingType(string $content): ?string
    {
        $lower = strtolower($content);
        if (str_contains($lower, 'grant')) return 'grant';
        if (str_contains($lower, 'equity')) return 'equity';
        if (str_contains($lower, 'debt')) return 'debt';
        if (str_contains($lower, 'prize')) return 'prize';
        return null;
    }

    protected function extractDeadline(string $content): ?string
    {
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $content, $m)) {
            $date = $m[1];
            return Carbon::parse($date)->gte(now()->startOfDay()) ? $date : null;
        }
        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})/', $content, $m)) {
            $date = Carbon::createFromFormat('m/d/Y', $m[1])->format('Y-m-d');
            return Carbon::parse($date)->gte(now()->startOfDay()) ? $date : null;
        }
        return null;
    }

    protected function extractFundingMin(string $content): ?float
    {
        if (preg_match('/\$?([\d,]+)\s*(?:k|K|000)/', $content, $m)) {
            return (float) str_replace(',', '', $m[1]) * 1000;
        }
        if (preg_match('/\$?([\d,]+)\s*(?:m|M|million)/', $content, $m)) {
            return (float) str_replace(',', '', $m[1]) * 1000000;
        }
        return null;
    }

    protected function extractFundingMax(string $content): ?float
    {
        return $this->extractFundingMin($content);
    }

    protected function normalizeFundingType(?string $type): ?string
    {
        if (!$type) return null;
        $valid = ['grant', 'equity', 'debt', 'prize', 'other'];
        $lower = strtolower(trim($type));
        return in_array($lower, $valid) ? $lower : null;
    }

    protected function parseFundingAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $str = (string) $value;
        if (preg_match('/\$?([\d,]+)\s*(?:k|K|000)/i', $str, $m)) {
            return (float) str_replace(',', '', $m[1]) * 1000;
        }
        if (preg_match('/\$?([\d,]+)\s*(?:m|M|million)/i', $str, $m)) {
            return (float) str_replace(',', '', $m[1]) * 1000000;
        }
        return null;
    }
}

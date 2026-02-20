<?php

namespace App\services\AI;

use App\services\GeminiService;
use App\services\Scraping\ContentSanitizer;
use Carbon\Carbon;

class OpportunityExtractor
{
    public function __construct(
        protected GeminiService $geminiService,
        protected ContentSanitizer $contentSanitizer
    ) {}

    public function extract(string $rawContent): ?array
    {
        $content = $this->contentSanitizer->sanitize($rawContent);
        $content = strlen($content) >= 50 ? $content : $rawContent;
        $extracted = $this->callAI($content);

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
        $content = $this->contentSanitizer->sanitize($rawContent);
        $content = strlen($content) >= 50 ? $content : $rawContent;
        $extracted = $this->callAI($content);

        if (!$extracted) {
        return [
            'title' => null,
            'description' => null,
            'funding_type' => null,
            'deadline' => null,
            'industry' => null,
            'stage' => null,
            'country' => null,
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
            'title' => $this->sanitizeText($extracted['title'] ?? 'Untitled'),
            'description' => $this->sanitizeText($extracted['description'] ?? null),
            'funding_type' => $this->normalizeFundingType($extracted['funding_type'] ?? null),
            'deadline' => $deadline,
            'industry' => $this->sanitizeText($extracted['industry'] ?? null),
            'stage' => $extracted['stage'] ?? null,
            'country' => $this->sanitizeText($extracted['country'] ?? null),
            'funding_min' => $this->parseFundingAmount($extracted['funding_min'] ?? null),
            'funding_max' => $this->parseFundingAmount($extracted['funding_max'] ?? null),
        ];
    }

    /**
     * Decode HTML entities and clean text for display.
     * e.g. "fundsforNGOs &#8211; Grants" → "fundsforNGOs – Grants"
     */
    protected function sanitizeText(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }
        $cleaned = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleaned = strip_tags($cleaned);
        $cleaned = trim($cleaned);

        return $cleaned === '' ? null : $cleaned;
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
            'description' => $this->extractDescription($content),
            'funding_type' => $this->extractFundingType($content),
            'deadline' => $this->extractDeadline($content),
            'industry' => null,
            'stage' => null,
            'country' => null,
            'funding_min' => $this->extractFundingMin($content),
            'funding_max' => $this->extractFundingMax($content),
        ];
    }

    protected function extractDescription(string $content): ?string
    {
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $content, $m)) {
            return $this->sanitizeText($m[1]);
        }
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/i', $content, $m)) {
            return $this->sanitizeText($m[1]);
        }
        if (preg_match('/<p[^>]*>([^<]{50,1500})<\/p>/i', $content, $m)) {
            return $this->sanitizeText($m[1]);
        }
        if (preg_match('/<div[^>]*class="[^"]*description[^"]*"[^>]*>([\s\S]{100,2000})<\/div>/i', $content, $m)) {
            return $this->sanitizeText(strip_tags($m[1]));
        }
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        if (strlen($text) > 100) {
            return $this->sanitizeText(mb_substr($text, 0, 1500));
        }
        return null;
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
        $parsed = $this->parseFrenchDateRange($content);
        if ($parsed) {
            return $parsed;
        }
        return null;
    }

    private function parseFrenchDateRange(string $content): ?string
    {
        $months = [
            'janvier' => '01', 'janv' => '01', 'février' => '02', 'févr' => '02', 'fevrier' => '02',
            'mars' => '03', 'avril' => '04', 'avr' => '04', 'mai' => '05', 'juin' => '06',
            'juillet' => '07', 'juil' => '07', 'août' => '08', 'aout' => '08', 'septembre' => '09',
            'sept' => '09', 'octobre' => '10', 'oct' => '10', 'novembre' => '11', 'nov' => '11',
            'décembre' => '12', 'dec' => '12', 'décem' => '12',
        ];
        if (preg_match('/au\s+(\d{1,2})\s+(janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|janv|févr|avr|juil|sept|oct|nov|décem|dec)\s+(\d{4})/ui', $content, $m)) {
            $month = $months[strtolower(trim($m[2]))] ?? null;
            if ($month) {
                $date = sprintf('%d-%s-%02d', (int) $m[3], $month, (int) $m[1]);
                return Carbon::parse($date)->gte(now()->startOfDay()) ? $date : null;
            }
        }
        if (preg_match('/(\d{1,2})\s+(janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|janv|févr|avr|juil|sept|oct|nov|décem|dec)\s+(\d{4})/ui', $content, $m)) {
            $month = $months[strtolower(trim($m[2]))] ?? null;
            if ($month) {
                $date = sprintf('%d-%s-%02d', (int) $m[3], $month, (int) $m[1]);
                return Carbon::parse($date)->gte(now()->startOfDay()) ? $date : null;
            }
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
        $cleaned = $this->sanitizeText($type);
        if (!$cleaned) return null;
        $valid = ['grant', 'equity', 'debt', 'prize', 'other'];
        $lower = strtolower(trim($cleaned));
        if (in_array($lower, $valid)) return $lower;
        if (str_contains($lower, 'hackathon') || str_contains($lower, 'competition') || str_contains($lower, 'challenge')) return 'prize';
        return null;
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

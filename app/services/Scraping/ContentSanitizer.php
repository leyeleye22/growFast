<?php

namespace App\services\Scraping;

/**
 * Sanitizes raw HTML before opportunity extraction.
 * Removes scripts, styles, extracts main article content, and produces clean text.
 */
class ContentSanitizer
{
    /** Selectors for main content containers (in order of preference). */
    protected array $mainContentSelectors = [
        'article',
        '[role="main"]',
        'main',
        '.entry-content',
        '.post-content',
        '.article-content',
        '.content-body',
        '.single-content',
        '#content',
        '.main-content',
        '.page-content',
    ];

    /**
     * Sanitize raw HTML: strip noise, extract main content, return clean text.
     */
    public function sanitize(string $rawHtml): string
    {
        $content = $this->stripNoise($rawHtml);
        $mainContent = $this->extractMainContent($content);

        if ($mainContent === '') {
            $mainContent = $this->stripTagsFallback($content);
        }

        $mainContent = $this->removeDuplicateBlocks($mainContent);
        $mainContent = $this->fixMarkdown($mainContent);

        return trim($mainContent);
    }

    /**
     * Remove script, style, noscript, inline JS protection, comments.
     */
    public function stripNoise(string $html): string
    {
        $content = $html;

        // Remove script tags and content
        $content = preg_replace('/<script[^>]*>[\s\S]*?<\/script>/ui', ' ', $content);
        // Remove style tags and content
        $content = preg_replace('/<style[^>]*>[\s\S]*?<\/style>/ui', ' ', $content);
        // Remove noscript
        $content = preg_replace('/<noscript[^>]*>[\s\S]*?<\/noscript>/ui', ' ', $content);
        // Remove HTML comments
        $content = preg_replace('/<!--[\s\S]*?-->/u', ' ', $content);
        // Remove inline event handlers (onclick, onload, etc.)
        $content = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/ui', ' ', $content);
        // Remove common anti-copy / protection patterns
        $content = preg_replace('/<div[^>]*class="[^"]*no-select[^"]*"[^>]*>[\s\S]*?<\/div>/ui', ' ', $content);
        $content = preg_replace('/user-select\s*:\s*none/iu', ' ', $content);

        return $content;
    }

    /**
     * Extract main article content using DOM or regex fallback.
     */
    protected function extractMainContent(string $html): string
    {
        try {
            $html = $this->ensureUtf8($html);

            libxml_use_internal_errors(true);
            $dom = new \DOMDocument;
            $loaded = @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            if (! $loaded || ! $dom->documentElement) {
                return '';
            }

            $xpath = new \DOMXPath($dom);

            foreach ($this->mainContentSelectors as $selector) {
                $nodes = $this->querySelector($xpath, $selector);
                foreach ($nodes as $node) {
                    $text = $this->getTextContent($node);
                    if (strlen($text) >= 100) {
                        return $text;
                    }
                }
            }

            // Fallback: first div with substantial content
            $divs = $dom->getElementsByTagName('div');
            foreach ($divs as $div) {
                $text = $this->getTextContent($div);
                if (strlen($text) >= 200) {
                    return $text;
                }
            }

            // Last resort: body or document
            $body = $dom->getElementsByTagName('body')->item(0);
            if ($body) {
                return $this->getTextContent($body);
            }
            return $this->getTextContent($dom->documentElement);
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function querySelector(\DOMXPath $xpath, string $selector): \DOMNodeList
    {
        $selector = trim($selector);
        if ($selector === '') {
            return new \DOMNodeList;
        }

        // Simple tag name
        if (preg_match('/^[a-z][a-z0-9]*$/i', $selector)) {
            return $xpath->query('//' . $selector);
        }
        // ID
        if (str_starts_with($selector, '#')) {
            return $xpath->query('//*[@id="' . substr($selector, 1) . '"]');
        }
        // Class
        if (str_starts_with($selector, '.')) {
            $class = str_replace('.', '', $selector);
            return $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]');
        }
        // Attribute
        if (str_contains($selector, '[')) {
            preg_match('/^([a-z*]+)\[([^\]]+)\]$/i', $selector, $m);
            $tag = $m[1] ?? '*';
            $attr = $m[2] ?? '';
            if (str_contains($attr, '=')) {
                [$name, $val] = explode('=', $attr, 2);
                $val = trim($val, '"\'');
                return $xpath->query('//' . $tag . '[@' . trim($name) . '="' . $val . '"]');
            }
        }

        return new \DOMNodeList;
    }

    protected function getTextContent(\DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($child->nodeName ?? '');
                if (in_array($tag, ['script', 'style', 'noscript'], true)) {
                    continue;
                }
                $text .= $this->getTextContent($child);
                if (in_array($tag, ['p', 'div', 'br', 'li', 'h1', 'h2', 'h3', 'h4', 'tr'], true)) {
                    $text .= "\n";
                }
            }
        }
        return $text;
    }

    protected function stripTagsFallback(string $html): string
    {
        $content = strip_tags($html);
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/\s+/', ' ', $content);
        return trim($content);
    }

    protected function removeDuplicateBlocks(string $text): string
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $seen = [];
        $unique = [];
        foreach ($lines as $line) {
            $norm = preg_replace('/\s+/', ' ', strtolower($line));
            if (strlen($norm) < 20) {
                continue;
            }
            if (isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $unique[] = $line;
        }
        return implode("\n\n", $unique);
    }

    protected function fixMarkdown(string $text): string
    {
        // Fix broken headers (### without space, ##text)
        $text = preg_replace('/^#{1,6}([^#\s])/m', '#$1', $text);
        // Remove excessive symbols
        $text = preg_replace('/^[\*\-\=\_]{3,}\s*$/m', '', $text);
        // Normalize multiple newlines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    protected function ensureUtf8(string $html): string
    {
        $encoding = mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $html = mb_convert_encoding($html, 'UTF-8', $encoding);
        }
        return $html;
    }

    /**
     * Check if a URL host is blacklisted.
     */
    public function isBlacklisted(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return false;
        }
        $blacklist = config('scraping.blacklist') ?? [];
        if (! is_array($blacklist)) {
            return false;
        }
        foreach ($blacklist as $pattern) {
            if (str_contains($host, $pattern) || fnmatch($pattern, $host)) {
                return true;
            }
        }
        return false;
    }
}

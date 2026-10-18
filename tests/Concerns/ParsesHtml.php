<?php

namespace Tests\Concerns;

use DOMDocument;
use DOMXPath;

/** Small HTML helpers for asserting on rendered public pages. */
trait ParsesHtml
{
    protected function dom(string $html): DOMXPath
    {
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($doc);
    }

    /** First matching attribute value for an XPath query, or null. */
    protected function attr(string $html, string $query, string $attribute): ?string
    {
        $node = $this->dom($html)->query($query)->item(0);

        return $node?->attributes?->getNamedItem($attribute)?->nodeValue;
    }

    protected function meta(string $html, string $key, string $value = 'name'): ?string
    {
        return $this->attr($html, "//meta[@{$value}='{$key}']", 'content');
    }

    /** @return array<int, array<string, mixed>> decoded JSON-LD blocks */
    protected function jsonLdBlocks(string $html): array
    {
        $blocks = [];
        foreach ($this->dom($html)->query("//script[@type='application/ld+json']") as $script) {
            $decoded = json_decode($script->textContent, true, flags: JSON_THROW_ON_ERROR);
            $blocks[] = $decoded;
        }

        return $blocks;
    }

    /** @return array<int, array<string, mixed>> all nodes across every JSON-LD @graph */
    protected function jsonLdNodes(string $html): array
    {
        $nodes = [];
        foreach ($this->jsonLdBlocks($html) as $block) {
            foreach ($block['@graph'] ?? [$block] as $node) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /** @return array<int, array<string, mixed>> */
    protected function jsonLdOfType(string $html, string $type): array
    {
        return array_values(array_filter($this->jsonLdNodes($html), fn ($n) => ($n['@type'] ?? null) === $type));
    }

    /** Count of <script> tags that are not application/ld+json. */
    protected function nonJsonLdScripts(string $html): int
    {
        $count = 0;
        foreach ($this->dom($html)->query('//script') as $script) {
            if ($script->attributes->getNamedItem('type')?->nodeValue !== 'application/ld+json') {
                $count++;
            }
        }

        return $count;
    }
}

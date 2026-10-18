<?php

namespace App\Support;

/**
 * Everything the public <head> needs for one page. Controllers build it;
 * components/public/layout.blade.php renders it.
 */
class Seo
{
    /** @var array<int, array<string, mixed>> JSON-LD nodes merged into one @graph */
    public array $schema = [];

    /** @var array<int, array{property: string, content: string}> extra <meta property> tags */
    public array $meta = [];

    /** @var array<int, array{rel: string, href: string, type?: string, title?: string}> */
    public array $links = [];

    public ?string $image = null;

    public ?string $imageAlt = null;

    /** LCP image to preload (src + optional srcset/sizes) */
    public ?array $preloadImage = null;

    public string $type = 'website';

    public string $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
    ) {}

    public static function make(string $title, string $description, ?string $canonical = null): self
    {
        return new self($title, $description, $canonical ?? url()->current());
    }

    public function fullTitle(): string
    {
        $site = config('blog.name');

        return str_contains($this->title, $site) ? $this->title : "{$this->title} | {$site}";
    }

    public function noindex(): self
    {
        $this->robots = 'noindex, follow';

        return $this;
    }

    /** @param array<string, mixed> $node */
    public function addSchema(array $node): self
    {
        $this->schema[] = $node;

        return $this;
    }

    public function jsonLd(): string
    {
        $graph = array_merge([Schema::organization(), Schema::website()], $this->schema);

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
        );
    }
}

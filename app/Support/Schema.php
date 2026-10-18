<?php

namespace App\Support;

use App\Models\Article;
use App\Models\User;

/**
 * schema.org JSON-LD builders. Nodes reference each other by @id so the
 * whole page is one connected graph (Organization → WebSite → BlogPosting →
 * Person), which is what search engines and LLM crawlers parse best.
 */
class Schema
{
    public static function base(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /** Who publishes the site: name, logo, URL (Google's "Organization" schema). */
    public static function organization(): array
    {
        return array_filter([
            '@type' => 'Organization',
            '@id' => self::base().'/#organization',
            'name' => config('blog.name'),
            'url' => self::base().'/',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => self::base().config('blog.logo'),
                'width' => 512,
                'height' => 512,
            ],
            'sameAs' => config('blog.same_as') ?: null,
        ]);
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::base().'/#website',
            'url' => self::base().'/',
            'name' => config('blog.name'),
            'inLanguage' => 'en',
            'publisher' => ['@id' => self::base().'/#organization'],
        ];
    }

    public static function person(User $user): array
    {
        return array_filter([
            '@type' => 'Person',
            '@id' => $user->authorUrl().'#person',
            'name' => $user->name,
            'url' => $user->authorUrl(),
            'image' => $user->avatar ?: null,
            'jobTitle' => $user->headline ?: null,
            'description' => $user->bio ? str($user->bio)->limit(300)->toString() : null,
            'sameAs' => array_values($user->socialProfiles()) ?: null,
        ]);
    }

    public static function blogPosting(Article $article): array
    {
        $url = $article->url();

        return array_filter([
            '@type' => 'BlogPosting',
            '@id' => $url.'#article',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'headline' => str($article->title)->limit(110, '')->toString(),
            'description' => $article->metaDescription(),
            'image' => $article->featured_image ? array_filter([
                '@type' => 'ImageObject',
                'url' => $article->featuredImageUrl(1200),
                'width' => 1200,
                'height' => 675,
                'caption' => $article->featured_image_caption ?: $article->featured_image_alt,
            ]) : null,
            'datePublished' => $article->published_at?->toAtomString(),
            'dateModified' => $article->updated_at?->toAtomString(),
            'author' => $article->author ? self::person($article->author) : null,
            'publisher' => ['@id' => self::base().'/#organization'],
            'isPartOf' => ['@id' => self::base().'/#website'],
            'articleSection' => $article->category?->name,
            'keywords' => $article->tags->pluck('name')->implode(', ') ?: null,
            'wordCount' => $article->word_count ?: null,
            'timeRequired' => "PT{$article->read_time_minutes}M",
            'inLanguage' => 'en',
            'speakable' => ['@type' => 'SpeakableSpecification', 'cssSelector' => ['h1', '.article-excerpt']],
        ]);
    }

    /** @param array<int, array{0: string, 1: string}> $items [name, url] */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item[0],
                'item' => $item[1],
            ])->all(),
        ];
    }

    /** @param array<int, array{question: string, answer: string}> $pairs */
    public static function faq(array $pairs, string $url): array
    {
        return [
            '@type' => 'FAQPage',
            '@id' => $url.'#faq',
            'mainEntity' => collect($pairs)->map(fn ($p) => [
                '@type' => 'Question',
                'name' => $p['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $p['answer']],
            ])->all(),
        ];
    }

    /** @param iterable<Article> $articles */
    public static function collection(string $name, string $url, iterable $articles): array
    {
        return [
            '@type' => 'CollectionPage',
            '@id' => $url.'#webpage',
            'name' => $name,
            'url' => $url,
            'isPartOf' => ['@id' => self::base().'/#website'],
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => collect($articles)->values()->map(fn (Article $a, $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => $a->url(),
                    'name' => $a->title,
                ])->all(),
            ],
        ];
    }
}

<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\Blog\ArticleWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ParsesHtml;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use ParsesHtml, RefreshDatabase;

    private User $author;

    private Category $category;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['username' => 'ada', 'name' => 'Ada Lovelace']);
        $this->category = Category::factory()->create(['name' => 'Engines', 'slug' => 'engines']);

        $this->article = app(ArticleWriter::class)->save(new Article, [
            'author_id' => $this->author->id,
            'category_id' => $this->category->id,
            'title' => 'Analytical Engines Explained',
            'meta_description' => 'A short description for search engines.',
            'body' => '<h2>Why engines?</h2><p>Because they compute things, and that is a good enough reason to read on.</p>'
                .'<h2>How they work?</h2><p>With gears, and plenty of patience from everybody involved in the process.</p>',
            'featured_image' => 'https://res.cloudinary.com/demo/image/upload/v1/blog/engine.jpg',
            'featured_image_alt' => 'An engine',
            'status' => 'published',
            'tags' => ['Gears', 'History'],
        ]);
    }

    private function articleHtml(): string
    {
        return $this->get("/blog/{$this->article->slug}")->assertOk()->getContent();
    }

    public function test_article_page_has_canonical_and_social_tags(): void
    {
        $html = $this->articleHtml();
        $url = $this->article->url();

        $this->assertSame($url, $this->attr($html, "//link[@rel='canonical']", 'href'));
        $this->assertStringContainsString('Analytical Engines Explained | ', $this->dom($html)->query('//title')->item(0)->textContent);
        $this->assertSame('A short description for search engines.', $this->meta($html, 'description'));
        $this->assertStringStartsWith('index, follow', $this->meta($html, 'robots'));

        $this->assertSame('article', $this->meta($html, 'og:type', 'property'));
        $this->assertSame($url, $this->meta($html, 'og:url', 'property'));
        $this->assertSame('Analytical Engines Explained', $this->meta($html, 'og:title', 'property'));
        $this->assertStringContainsString('res.cloudinary.com', $this->meta($html, 'og:image', 'property'));
        $this->assertSame('An engine', $this->meta($html, 'og:image:alt', 'property'));
        $this->assertSame('summary_large_image', $this->meta($html, 'twitter:card'));
        $this->assertSame('Analytical Engines Explained', $this->meta($html, 'twitter:title'));
        $this->assertSame('A short description for search engines.', $this->meta($html, 'twitter:description'));
        $this->assertNotNull($this->meta($html, 'twitter:image'));
        $this->assertSame('Engines', $this->meta($html, 'article:section', 'property'));
        $this->assertNotNull($this->meta($html, 'article:published_time', 'property'));
        $this->assertSame(route('blog.markdown', $this->article), $this->attr($html, "//link[@rel='alternate'][@type='text/markdown']", 'href'));
    }

    public function test_article_json_ld_parses_with_blog_posting_and_breadcrumbs(): void
    {
        $html = $this->articleHtml();
        $url = $this->article->url();

        $posting = $this->jsonLdOfType($html, 'BlogPosting');
        $this->assertCount(1, $posting);
        $this->assertSame('Analytical Engines Explained', $posting[0]['headline']);
        $this->assertSame('Ada Lovelace', $posting[0]['author']['name']);
        $this->assertSame($url, $posting[0]['mainEntityOfPage']['@id']);
        $this->assertSame('Engines', $posting[0]['articleSection']);

        $crumbs = $this->jsonLdOfType($html, 'BreadcrumbList');
        $this->assertCount(1, $crumbs);
        $items = $crumbs[0]['itemListElement'];
        $this->assertSame(['Home', 'Blog', 'Engines', 'Analytical Engines Explained'], array_column($items, 'name'));
        $this->assertSame([1, 2, 3, 4], array_column($items, 'position'));
        $this->assertSame($url, end($items)['item']);

        // Two question headings make an FAQPage.
        $this->assertCount(1, $this->jsonLdOfType($html, 'FAQPage'));
        $this->assertCount(1, $this->jsonLdOfType($html, 'Organization'));
        $this->assertCount(1, $this->jsonLdOfType($html, 'WebSite'));
    }

    public function test_public_pages_ship_no_javascript_except_json_ld(): void
    {
        $tag = Tag::where('name', 'Gears')->firstOrFail();
        $uris = [
            '/',
            '/blog',
            "/blog/{$this->article->slug}",
            '/blog/category/engines',
            "/blog/tag/{$tag->slug}",
            '/blog/author/ada',
            '/blog/search?q=engines',
        ];

        foreach ($uris as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();
            $this->assertSame(0, $this->nonJsonLdScripts($html), "Unexpected <script> on {$uri}");
            $this->assertGreaterThanOrEqual(1, count($this->jsonLdBlocks($html)), "No JSON-LD on {$uri}");
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $this->stripJsonLd($html), "Inline handler on {$uri}");
        }
    }

    private function stripJsonLd(string $html): string
    {
        return preg_replace('~<script type="application/ld\+json">.*?</script>~s', '', $html);
    }

    public function test_article_page_featured_image_is_eager_and_high_priority(): void
    {
        $img = $this->dom($this->articleHtml())->query('//figure//img')->item(0);

        $this->assertSame('eager', $img->getAttribute('loading'));
        $this->assertSame('high', $img->getAttribute('fetchpriority'));
        $this->assertSame('An engine', $img->getAttribute('alt'));
    }

    public function test_listing_first_image_is_eager_and_the_rest_are_lazy(): void
    {
        Article::factory()->count(3)->withImage()->create();
        $this->article->update(['published_at' => now()->subDays(200)]); // make it the oldest

        $imgs = $this->dom($this->get('/blog')->assertOk()->getContent())->query('//article//img');
        $this->assertGreaterThanOrEqual(4, $imgs->length);

        $this->assertSame('eager', $imgs->item(0)->getAttribute('loading'));
        $this->assertSame('high', $imgs->item(0)->getAttribute('fetchpriority'));

        for ($i = 1; $i < $imgs->length; $i++) {
            $this->assertSame('lazy', $imgs->item($i)->getAttribute('loading'), "Image {$i} should be lazy");
            $this->assertFalse($imgs->item($i)->hasAttribute('fetchpriority'));
        }
    }

    public function test_blog_index_canonical_and_pagination_are_path_based(): void
    {
        Article::factory()->count(13)->create(['author_id' => $this->author->id]); // + 1 = 14 > 12 per page

        $first = $this->get('/blog')->assertOk()->getContent();
        $this->assertSame(route('blog.index'), $this->attr($first, "//link[@rel='canonical']", 'href'));
        $this->assertSame(url('/blog/page/2'), $this->attr($first, "//link[@rel='next']", 'href'));
        $this->assertNull($this->attr($first, "//link[@rel='prev']", 'href'));

        $second = $this->get('/blog/page/2')->assertOk()->getContent();
        $this->assertSame(url('/blog/page/2'), $this->attr($second, "//link[@rel='canonical']", 'href'));
        $this->assertSame(route('blog.index'), $this->attr($second, "//link[@rel='prev']", 'href'));
        $this->assertStringContainsString('Page 2', $this->dom($second)->query('//title')->item(0)->textContent);
        $this->assertStringNotContainsString('?page=', $second);

        $this->get('/blog/page/1')->assertStatus(301)->assertRedirect(route('blog.index'));
        $this->get('/blog/page/99')->assertNotFound();
        $this->get('/blog/category/engines/page/1')->assertStatus(301)->assertRedirect(route('blog.category', 'engines'));
    }

    public function test_search_page_is_noindex_and_finds_published_articles(): void
    {
        $html = $this->get('/blog/search?q=analytical')->assertOk()->assertSee('Analytical Engines Explained')->getContent();

        $this->assertStringStartsWith('noindex', $this->meta($html, 'robots'));
        $this->assertSame(route('blog.search'), $this->attr($html, "//link[@rel='canonical']", 'href'));

        $empty = $this->get('/blog/search')->assertOk()->getContent();
        $this->assertStringStartsWith('noindex', $this->meta($empty, 'robots'));
    }

    public function test_listing_json_ld_has_collection_and_breadcrumbs(): void
    {
        $html = $this->get('/blog/category/engines')->assertOk()->getContent();

        $this->assertCount(1, $this->jsonLdOfType($html, 'CollectionPage'));
        $crumbs = $this->jsonLdOfType($html, 'BreadcrumbList')[0]['itemListElement'];
        $this->assertSame(['Home', 'Blog', 'Engines'], array_column($crumbs, 'name'));
    }

    public function test_author_redirects_id_to_username_and_has_person_schema(): void
    {
        $this->get("/blog/author/{$this->author->id}")->assertStatus(301)->assertRedirect($this->author->authorUrl());

        $html = $this->get('/blog/author/ada')->assertOk()->getContent();
        $this->assertNotEmpty($this->jsonLdOfType($html, 'Person'));
    }

    public function test_titles_and_search_queries_are_escaped(): void
    {
        $evil = '<script>alert("xss")</script>';
        $article = Article::factory()->create(['title' => "Dangerous {$evil}", 'author_id' => $this->author->id]);

        foreach (['/blog', "/blog/{$article->slug}", '/', '/sitemap.xml'] as $uri) {
            $response = $this->get($uri)->assertOk();
            $this->assertStringNotContainsString($evil, $response->getContent(), "Unescaped title on {$uri}");
        }
        $html = $this->get("/blog/{$article->slug}")->getContent();
        $this->assertStringContainsString('Dangerous &lt;script&gt;', $html);
        $this->assertSame(0, $this->nonJsonLdScripts($html));
        $this->jsonLdBlocks($html); // still valid JSON-LD

        $q = '<script>alert(1)</script>';
        $search = $this->get('/blog/search?q='.urlencode($q))->assertOk()->getContent();
        $this->assertStringNotContainsString($q, $search);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $search);
        $this->assertSame(0, $this->nonJsonLdScripts($search));

        $feed = $this->get('/blog/feed.xml')->assertOk()->getContent();
        $this->assertNotFalse(simplexml_load_string($feed), 'Feed must be well-formed XML');
        $this->assertNotFalse(simplexml_load_string($this->get('/sitemap.xml')->getContent()));
    }

    public function test_robots_and_sitemap_endpoints(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:', false);
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString($this->article->url(), $sitemap);
        $this->assertStringContainsString('/blog/category/engines', $sitemap);
    }
}

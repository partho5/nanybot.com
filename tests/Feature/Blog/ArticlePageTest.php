<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Services\Blog\ArticleWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ParsesHtml;
use Tests\TestCase;

class ArticlePageTest extends TestCase
{
    use ParsesHtml, RefreshDatabase;

    private User $author;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create([
            'username' => 'ada',
            'name' => 'Ada Lovelace',
            'headline' => 'Writes about engines',
            'bio' => 'Ada writes guides for small-business owners.',
            'social_links' => ['LinkedIn' => 'https://www.linkedin.com/in/ada', 'Site' => 'https://ada.example'],
        ]);

        $this->article = app(ArticleWriter::class)->save(new Article, [
            'author_id' => $this->author->id,
            'category_id' => Category::factory()->create()->id,
            'title' => 'Automating Invoices',
            'body' => '<h2>Why automate?</h2><p>Because it repeats every single week and nobody enjoys doing it by hand.</p>'
                .'<h2>Setup</h2><pre><code class="language-php">echo "hi";</code></pre>'
                .'<div data-type="callout" data-variant="amber" data-label="Heads up"><p>Back up first.</p></div>'
                .'<h3>Details</h3><p>More words.</p>',
            'status' => 'published',
            'tags' => ['Invoices'],
        ]);
    }

    private function html(): string
    {
        return $this->get($this->article->url())->assertOk()->getContent();
    }

    public function test_product_cta_renders_from_config(): void
    {
        config(['blog.cta.headline' => 'Stop doing it by hand.']);

        $xpath = $this->dom($this->html());
        $cta = $xpath->query("//main//aside[contains(@class, 'bg-ink')]")->item(0);

        $this->assertNotNull($cta);
        $this->assertStringContainsString('Stop doing it by hand.', $cta->textContent);
        $this->assertSame(1, $xpath->query(".//a[@href='/#software']", $cta)->length);
        $this->assertSame(1, $xpath->query(".//a[@href='".config('blog.cta.secondary.url')."']", $cta)->length);
    }

    public function test_author_card_shows_about_the_author_with_published_article_count(): void
    {
        Article::factory()->create(['author_id' => $this->author->id]);
        Article::factory()->draft()->create(['author_id' => $this->author->id]);

        $xpath = $this->dom($this->html());
        $card = $xpath->query("//main//aside[@aria-labelledby='author-card-title']")->item(0);

        $this->assertNotNull($card);
        $this->assertStringContainsString('About the author', $card->textContent);
        $this->assertStringContainsString('All articles by Ada Lovelace', $card->textContent);
        $this->assertStringContainsString('2 articles', $card->textContent); // the draft is not counted
        $this->assertSame(1, $xpath->query(".//a[@href='https://www.linkedin.com/in/ada'][contains(., 'LinkedIn')]//svg", $card)->length);
        $this->assertStringContainsString('Ada writes guides for small-business owners.', $card->textContent);
    }

    public function test_sections_after_the_body_are_in_order(): void
    {
        $html = $this->html();
        $positions = array_map(fn ($needle) => strpos($html, $needle), [
            'class="article-body"', 'rel="tag"', 'Share on X', 'About the author', config('blog.cta.headline'),
        ]);

        $this->assertNotContains(false, $positions);
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
    }

    public function test_js_only_controls_are_absent_or_hidden_without_javascript(): void
    {
        $xpath = $this->dom($this->html());

        $this->assertSame(0, $xpath->query("//*[contains(@class, 'code-copy')]")->length);
        $copyLink = $xpath->query("//button[contains(@class, 'js-copy-link')]")->item(0);
        $this->assertNotNull($copyLink);
        $this->assertTrue($copyLink->hasAttribute('hidden'));
    }

    public function test_code_blocks_are_wrapped_with_their_language_and_stored_html_is_untouched(): void
    {
        $xpath = $this->dom($this->html());

        $block = $xpath->query("//div[contains(@class, 'article-body')]//div[@class='code-block']")->item(0);
        $this->assertSame('php', $block->getAttribute('data-lang'));
        $this->assertSame(1, $xpath->query('./pre/code', $block)->length);
        $this->assertStringNotContainsString('code-block', $this->article->fresh()->body_html);
    }

    public function test_headings_get_server_side_permalinks_without_touching_stored_html(): void
    {
        $xpath = $this->dom($this->html());

        $anchor = $xpath->query("//div[contains(@class, 'article-body')]/h2[@id='why-automate']/a[@class='h-anchor']")->item(0);
        $this->assertNotNull($anchor);
        $this->assertSame('#why-automate', $anchor->getAttribute('href'));
        $this->assertSame('Link to section: Why automate?', $anchor->getAttribute('aria-label'));
        $this->assertSame(1, $xpath->query("//h3[@id='details']/a[@class='h-anchor']")->length);
        $this->assertStringNotContainsString('h-anchor', $this->article->fresh()->body_html);
    }

    public function test_admin_preview_still_renders_with_banner(): void
    {
        $this->article->update(['status' => 'draft']);

        $this->actingAs($this->author)
            ->get("/admin/articles/{$this->article->slug}/preview")
            ->assertOk()
            ->assertSee('Automating Invoices')
            ->assertSee('Only you can see this.')
            ->assertSee('Back to editor');
    }

    public function test_toc_renders_as_details_and_sticky_rail(): void
    {
        $xpath = $this->dom($this->html());

        $this->assertSame(3, $xpath->query("//details[contains(@class, 'toc-box')]//a")->length);
        $this->assertSame(3, $xpath->query("//aside[contains(@class, 'toc-rail')]//nav//a")->length);
        $this->assertSame(1, $xpath->query("//aside[contains(@class, 'toc-rail')]//a[@href='#why-automate']")->length);
    }

    public function test_related_cards_use_h3_headings(): void
    {
        $related = Article::factory()->create(['category_id' => $this->article->category_id, 'title' => 'Related One']);

        $xpath = $this->dom($this->html());

        $this->assertSame(1, $xpath->query("//section[@aria-labelledby='related-heading']//h3/a[@href='{$related->url()}']")->length);
        $this->assertSame(1, $xpath->query('//h1')->length);
    }

    public function test_author_page_has_a_single_h1_profile_header(): void
    {
        Article::factory()->create(['author_id' => $this->author->id]);

        $html = $this->get('/blog/author/ada')->assertOk()->getContent();
        $xpath = $this->dom($html);

        $h1 = $xpath->query('//h1');
        $this->assertSame(1, $h1->length);
        $this->assertStringContainsString('Ada Lovelace', $h1->item(0)->textContent);
        $this->assertStringContainsString('Ada writes guides', $html);
        $this->assertStringContainsString('2 articles', $html);
        $this->assertStringNotContainsString('Written by', $html);
    }
}

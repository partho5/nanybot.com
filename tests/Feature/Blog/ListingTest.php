<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ParsesHtml;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use ParsesHtml, RefreshDatabase;

    public function test_page_one_has_a_featured_item_and_its_preload_matches_the_image(): void
    {
        Article::factory()->withImage()->create(['title' => 'Newest Article', 'published_at' => now()->subDay()]);
        Article::factory()->count(2)->withImage()->create(['published_at' => now()->subDays(5)]);

        $html = $this->get('/blog')->assertOk()->getContent();
        $xpath = $this->dom($html);

        $featured = $xpath->query("//main//article[contains(@class, 'md:grid-cols-[7fr_5fr]')]");
        $this->assertSame(1, $featured->length);
        $this->assertStringContainsString('Newest Article', $featured->item(0)->textContent);

        $img = $xpath->query('.//img', $featured->item(0))->item(0);
        $this->assertStringContainsString('ar_16:10', $img->getAttribute('src'));
        $this->assertSame('1200', $img->getAttribute('width'));
        $this->assertSame('750', $img->getAttribute('height'));
        $this->assertSame($img->getAttribute('src'), $this->attr($html, "//link[@rel='preload']", 'href'));
        $this->assertSame($img->getAttribute('srcset'), $this->attr($html, "//link[@rel='preload']", 'imagesrcset'));
        $this->assertSame($img->getAttribute('sizes'), $this->attr($html, "//link[@rel='preload']", 'imagesizes'));
    }

    public function test_page_two_has_no_featured_item_and_preloads_the_first_card(): void
    {
        Article::factory()->count(14)->withImage()->create();

        $html = $this->get('/blog/page/2')->assertOk()->getContent();
        $xpath = $this->dom($html);

        $this->assertSame(0, $xpath->query("//main//article[contains(@class, 'md:grid-cols-[7fr_5fr]')]")->length);

        $img = $xpath->query('//main//article//img')->item(0);
        $this->assertSame('eager', $img->getAttribute('loading'));
        $this->assertSame('768', $img->getAttribute('width'));
        $this->assertSame('480', $img->getAttribute('height'));
        $this->assertSame($img->getAttribute('src'), $this->attr($html, "//link[@rel='preload']", 'href'));
        $this->assertSame($img->getAttribute('sizes'), $this->attr($html, "//link[@rel='preload']", 'imagesizes'));
    }

    public function test_articles_without_an_image_get_a_generated_cover(): void
    {
        Article::factory()->create(['title' => 'No Picture Here']);

        $html = $this->get('/blog')->assertOk()->getContent();
        $xpath = $this->dom($html);

        $this->assertSame(0, $xpath->query('//main//article//img')->length);
        $cover = $xpath->query("//main//article//div[@aria-hidden='true'][contains(@class, 'aspect-[16/10]')]");
        $this->assertSame(1, $cover->length);
        $this->assertStringContainsString('No Picture Here', $cover->item(0)->textContent);
        $this->assertNull($this->attr($html, "//link[@rel='preload'][@as='image']", 'href'));
    }

    public function test_card_title_link_is_stretched_and_meta_links_stay_clickable(): void
    {
        $article = Article::factory()->create();

        $xpath = $this->dom($this->get('/blog')->assertOk()->getContent());

        $title = $xpath->query("//main//article//h2/a[@href='{$article->url()}']")->item(0);
        $this->assertStringContainsString('after:inset-0', $title->getAttribute('class'));
        $category = $xpath->query("//main//article//a[@href='{$article->category->url()}']")->item(0);
        $this->assertStringContainsString('z-10', $category->getAttribute('class'));
    }

    public function test_empty_listing_shows_empty_state_with_link_to_all_articles(): void
    {
        Article::factory()->create();
        $tag = Tag::factory()->create(['slug' => 'nothing-here']);

        $html = $this->get($tag->url())->assertOk()->assertSee('No articles yet')->getContent();
        $this->assertNotNull($this->attr($html, "//main//a[@href='".route('blog.index')."'][contains(., 'All articles')]", 'href'));
    }
}

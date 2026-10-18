<?php

namespace Tests\Feature\Blog;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private Category $category;

    private Article $published;

    private Article $draft;

    private Article $scheduled;

    private Article $overdue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['username' => 'ada']);
        $this->category = Category::factory()->create();

        $common = ['author_id' => $this->author->id, 'category_id' => $this->category->id];
        $this->published = Article::factory()->published()->create($common + ['title' => 'Visible Published Post']);
        $this->draft = Article::factory()->draft()->create($common + ['title' => 'Secret Draft Post']);
        $this->scheduled = Article::factory()->scheduled(now()->addDays(2))->create($common + ['title' => 'Future Scheduled Post']);
        $this->overdue = Article::factory()->scheduled(now()->subMinute())->create($common + ['title' => 'Overdue Scheduled Post']);
    }

    public function test_published_article_is_visible_with_markdown_copy(): void
    {
        $this->get("/blog/{$this->published->slug}")->assertOk()->assertSee('Visible Published Post');
        $this->get("/blog/{$this->published->slug}.md")
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertSee('# Visible Published Post', false);
    }

    public function test_draft_and_future_scheduled_articles_are_404_everywhere(): void
    {
        foreach ([$this->draft, $this->scheduled] as $article) {
            $this->get("/blog/{$article->slug}")->assertNotFound();
            $this->get("/blog/{$article->slug}.md")->assertNotFound();
        }
    }

    public function test_a_draft_with_a_past_published_at_is_still_hidden(): void
    {
        // The status filter must hold on its own, not only because drafts normally have no date.
        $reverted = Article::factory()->draft()->create([
            'author_id' => $this->author->id,
            'title' => 'Reverted To Draft',
            'published_at' => now()->subWeek(),
        ]);

        $this->get("/blog/{$reverted->slug}")->assertNotFound();
        $this->get("/blog/{$reverted->slug}.md")->assertNotFound();
        $this->get('/blog')->assertOk()->assertDontSee('Reverted To Draft');
        $this->assertStringNotContainsString($reverted->slug, $this->get('/sitemap.xml')->getContent());
    }

    public function test_unknown_slug_is_404(): void
    {
        $this->get('/blog/does-not-exist')->assertNotFound();
        $this->get('/blog/does-not-exist.md')->assertNotFound();
    }

    public function test_scheduled_article_whose_time_has_passed_is_visible_before_the_scheduler_runs(): void
    {
        $this->assertSame(ArticleStatus::Scheduled, $this->overdue->fresh()->status);
        $this->get("/blog/{$this->overdue->slug}")->assertOk();
    }

    public function test_hidden_articles_are_absent_from_listings_sitemap_feed_and_llms(): void
    {
        $this->get('/blog')
            ->assertOk()
            ->assertSee('Visible Published Post')
            ->assertSee('Overdue Scheduled Post')
            ->assertDontSee('Secret Draft Post')
            ->assertDontSee('Future Scheduled Post');

        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString($this->published->slug, $sitemap);
        $this->assertStringNotContainsString($this->draft->slug, $sitemap);
        $this->assertStringNotContainsString($this->scheduled->slug, $sitemap);

        $feed = $this->get('/blog/feed.xml')->assertOk()->getContent();
        $this->assertStringContainsString('Visible Published Post', $feed);
        $this->assertStringNotContainsString('Secret Draft Post', $feed);
        $this->assertStringNotContainsString('Future Scheduled Post', $feed);

        $llms = $this->get('/llms.txt')->assertOk()->getContent();
        $this->assertStringContainsString('Visible Published Post', $llms);
        $this->assertStringNotContainsString('Secret Draft Post', $llms);
        $this->assertStringNotContainsString('Future Scheduled Post', $llms);

        $this->get('/')->assertOk()->assertDontSee('Secret Draft Post')->assertDontSee('Future Scheduled Post');
    }

    public function test_hidden_articles_are_absent_from_archives_and_search(): void
    {
        $tag = Tag::factory()->create();
        foreach ([$this->published, $this->draft, $this->scheduled] as $article) {
            $article->tags()->attach($tag);
        }

        foreach (["/blog/category/{$this->category->slug}", "/blog/tag/{$tag->slug}", '/blog/author/ada'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('Visible Published Post')
                ->assertDontSee('Secret Draft Post')
                ->assertDontSee('Future Scheduled Post');
        }

        $this->get('/blog/search?q=Secret')->assertOk()->assertDontSee('Secret Draft Post', false);
    }

    public function test_author_with_only_hidden_articles_has_no_public_page(): void
    {
        $lurker = User::factory()->create(['username' => 'lurker']);
        Article::factory()->draft()->create(['author_id' => $lurker->id]);

        $this->get('/blog/author/lurker')->assertNotFound();
        $this->assertStringNotContainsString('/blog/author/lurker', $this->get('/sitemap.xml')->getContent());
    }

    public function test_publish_scheduled_command_only_promotes_due_articles(): void
    {
        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $this->assertSame(ArticleStatus::Published, $this->overdue->fresh()->status);
        $this->assertSame(ArticleStatus::Scheduled, $this->scheduled->fresh()->status);
        $this->assertSame(ArticleStatus::Draft, $this->draft->fresh()->status);
    }

    public function test_admin_preview_shows_hidden_articles_to_their_owner_only(): void
    {
        $this->actingAs($this->author)
            ->get("/admin/articles/{$this->draft->slug}/preview")
            ->assertOk()
            ->assertSee('Secret Draft Post');

        $this->actingAs(User::factory()->create())
            ->get("/admin/articles/{$this->draft->slug}/preview")
            ->assertForbidden();
    }
}

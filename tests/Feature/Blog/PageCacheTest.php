<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\Blog\ArticleWriter;
use App\Services\Blog\PageCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PageCacheTest extends TestCase
{
    use RefreshDatabase;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        config(['blog.page_cache' => true]);

        $this->article = Article::factory()->published()->create();
    }

    private function cached(string $relative): string
    {
        return $this->pageCachePath().'/'.$relative;
    }

    /** Warm a page so there is something in the cache to flush. */
    private function warm(): void
    {
        $this->get('/blog')->assertOk();
        $this->assertFileExists($this->cached('blog.html'));
    }

    public function test_the_cache_directory_is_a_temp_dir_not_public(): void
    {
        $this->assertStringNotContainsString(public_path(), $this->pageCachePath());
        $this->assertSame($this->pageCachePath(), app(PageCache::class)->directory());
    }

    public function test_get_requests_write_the_expected_files(): void
    {
        $this->get('/')->assertOk();
        $this->get('/blog')->assertOk();
        $this->get("/blog/{$this->article->slug}")->assertOk();
        $this->get("/blog/{$this->article->slug}.md")->assertOk();
        $this->get('/sitemap.xml')->assertOk();
        $this->get('/blog/feed.xml')->assertOk();
        $this->get('/llms.txt')->assertOk();
        $this->get('/robots.txt')->assertOk();

        $this->assertFileExists($this->cached('index.html'));
        $this->assertFileExists($this->cached('blog.html'));
        $this->assertFileExists($this->cached("blog/{$this->article->slug}.html"));
        $this->assertFileExists($this->cached("blog/{$this->article->slug}.md"));
        $this->assertFileExists($this->cached('sitemap.xml'));
        $this->assertFileExists($this->cached('blog/feed.xml'));
        $this->assertFileExists($this->cached('llms.txt'));
        $this->assertFileExists($this->cached('robots.txt'));

        $this->assertStringContainsString($this->article->title, File::get($this->cached("blog/{$this->article->slug}.html")));
        $this->assertSame([], glob($this->pageCachePath().'/*.tmp') ?: [], 'No temp files may be left behind');
    }

    public function test_nothing_is_written_when_the_cache_is_disabled(): void
    {
        config(['blog.page_cache' => false]);

        $this->get('/blog')->assertOk();

        $this->assertDirectoryDoesNotExist($this->pageCachePath());
    }

    public function test_query_string_requests_are_not_cached(): void
    {
        $this->get('/blog/search?q=hello')->assertOk();
        $this->get('/blog?utm_source=x')->assertOk();

        $this->assertFileDoesNotExist($this->cached('blog/search.html'));
        $this->assertFileDoesNotExist($this->cached('blog.html'));
    }

    public function test_non_200_responses_are_not_cached(): void
    {
        $this->get('/blog/nope-not-here')->assertNotFound();
        $this->get('/blog/page/1')->assertRedirect();
        $this->get('/blog/page/99')->assertNotFound();

        $this->assertFileDoesNotExist($this->cached('blog/nope-not-here.html'));
        $this->assertFileDoesNotExist($this->cached('blog/page/1.html'));
        $this->assertFileDoesNotExist($this->cached('blog/page/99.html'));
    }

    public function test_hidden_articles_are_never_cached(): void
    {
        $draft = Article::factory()->draft()->create();

        $this->get("/blog/{$draft->slug}")->assertNotFound();

        $this->assertFileDoesNotExist($this->cached("blog/{$draft->slug}.html"));
    }

    public function test_non_get_requests_are_not_cached(): void
    {
        $this->post('/blog')->assertStatus(405);
        $this->assertFileDoesNotExist($this->cached('blog.html'));
    }

    public function test_cache_headers_are_set_on_public_pages(): void
    {
        $this->get('/blog')->assertOk()->assertHeader('Cache-Control');
    }

    public function test_saving_an_article_through_the_writer_flushes_the_cache(): void
    {
        $this->warm();

        app(ArticleWriter::class)->save($this->article, ['title' => 'Retitled']);

        $this->assertFileDoesNotExist($this->cached('blog.html'));
    }

    public function test_creating_and_deleting_articles_flush_the_cache(): void
    {
        $this->warm();
        Article::factory()->published()->create();
        $this->assertFileDoesNotExist($this->cached('blog.html'));

        $this->warm();
        $this->article->delete();
        $this->assertFileDoesNotExist($this->cached('blog.html'));
    }

    public function test_publishing_scheduled_articles_flushes_the_cache(): void
    {
        $scheduled = Article::factory()->scheduled(now()->subMinute())->create();
        $this->warm();

        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $this->assertFileDoesNotExist($this->cached('blog.html'));
        $this->assertSame('published', $scheduled->fresh()->status->value);
    }

    public function test_publish_command_leaves_the_cache_alone_when_nothing_is_due(): void
    {
        $this->warm();

        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $this->assertFileExists($this->cached('blog.html'));
    }

    public function test_editing_a_category_flushes_the_cache(): void
    {
        $category = Category::factory()->create();
        $this->warm();

        $category->update(['name' => 'Renamed']);

        $this->assertFileDoesNotExist($this->cached('blog.html'));
    }

    public function test_editing_a_tag_flushes_the_cache(): void
    {
        $tag = Tag::factory()->create();
        $this->warm();

        $tag->update(['name' => 'Renamed']);

        $this->assertFileDoesNotExist($this->cached('blog.html'));
    }

    public function test_editing_a_public_user_field_flushes_the_cache_but_a_login_touch_does_not(): void
    {
        $user = User::factory()->create();
        $user = User::findOrFail($user->id); // fresh instance: wasRecentlyCreated would always flush
        $this->warm();

        $user->update(['remember_token' => 'abc123']);
        $this->assertFileExists($this->cached('blog.html'), 'Non-public user fields must not flush');

        $user->update(['headline' => 'New headline']);
        $this->assertFileDoesNotExist($this->cached('blog.html'));
    }

    public function test_cache_clear_command_empties_the_cache(): void
    {
        $this->warm();

        $this->artisan('blog:cache-clear')->assertSuccessful();

        $this->assertFileDoesNotExist($this->cached('blog.html'));
    }
}

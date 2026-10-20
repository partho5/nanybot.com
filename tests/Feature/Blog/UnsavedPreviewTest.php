<?php

namespace Tests\Feature\Blog;

use App\Http\Middleware\CachePublicPage;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UnsavedPreviewTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/admin/articles/preview';

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'My Unsaved Draft',
            'slug' => '',
            'body' => '<h2>First section</h2><p>This paragraph is long enough to count as the opening paragraph of the draft.</p>',
            'status' => 'draft',
            'tags' => ['Brand New Tag'],
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->post(self::URL, $this->payload())->assertRedirect('/login');
    }

    public function test_renders_posted_title_and_processed_body_without_persisting(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(self::URL, $this->payload([
            'excerpt' => 'A short excerpt for the page.',
            'body' => '<h2>First section</h2><p>This paragraph is long enough to count as the opening paragraph.</p>'
                .'<script>alert(1)</script><p onclick="x()">Clickable</p>',
        ]))->assertOk();

        $response->assertSee('My Unsaved Draft');
        $response->assertSee('A short excerpt for the page.');
        // Same pipeline as a save: headings get ids, scripts and handlers are stripped.
        $response->assertSee('<h2 id="first-section">', false);
        $response->assertDontSee('alert(1)', false);
        $response->assertDontSee('onclick', false);

        $this->assertSame(0, Article::count());
        $this->assertSame(0, Tag::count());
    }

    public function test_banner_marks_it_unsaved_with_a_close_fallback_for_new_articles(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(self::URL, $this->payload())
            ->assertSee('Unsaved preview')
            ->assertSee('Only you can see this.')
            ->assertSee('Close preview');
    }

    public function test_sends_noindex_and_no_store_and_writes_no_page_cache(): void
    {
        $dir = sys_get_temp_dir().'/preview-cache-'.uniqid();
        config(['blog.page_cache_path' => $dir]);

        $response = $this->actingAs(User::factory()->create())
            ->post(self::URL, $this->payload())
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('noindex', (string) $response->getContent());
        $this->assertFalse(File::exists($dir), 'The preview must not write to the page cache.');
    }

    public function test_existing_article_previews_unsaved_edits_and_links_back_to_the_editor(): void
    {
        $author = User::factory()->create();
        $article = Article::factory()->published()->for($author, 'author')->create(['title' => 'Saved title']);
        $category = Category::factory()->create(['name' => 'Guides']);

        $this->actingAs($author)->post(self::URL, $this->payload([
            'article_id' => $article->id,
            'title' => 'Edited title',
            'slug' => $article->slug,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'publish_at' => now()->addWeek()->toIso8601String(),
        ]))
            ->assertOk()
            ->assertSee('Edited title')
            ->assertSee('Guides')
            ->assertSee('Unsaved preview')
            ->assertSee(route('admin.articles.edit', $article), false);

        $this->assertSame('Saved title', $article->fresh()->title);
    }

    public function test_author_cannot_preview_someone_elses_article(): void
    {
        $theirs = Article::factory()->draft()->create();

        $this->actingAs(User::factory()->create())
            ->post(self::URL, $this->payload(['article_id' => $theirs->id]))
            ->assertForbidden();
    }

    public function test_admin_can_preview_any_article(): void
    {
        $theirs = Article::factory()->draft()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(self::URL, $this->payload(['article_id' => $theirs->id, 'title' => 'Admin edit']))
            ->assertOk()
            ->assertSee('Admin edit');
    }

    public function test_route_is_csrf_protected_and_not_in_the_page_cached_group(): void
    {
        // CSRF verification is skipped under test, so assert the route sits in the web group that applies it.
        $route = Route::getRoutes()->getByName('admin.articles.preview.unsaved');
        $middleware = $route->gatherMiddleware();

        $this->assertSame(['POST'], $route->methods());
        $this->assertContains('web', $middleware);
        $this->assertContains('auth', $middleware);
        $this->assertNotContains(CachePublicPage::class, $middleware);
    }

    public function test_invalid_input_explains_itself_instead_of_redirecting(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(self::URL, $this->payload(['featured_image' => 'http://insecure.example/a.png']))
            ->assertStatus(422)
            ->assertSee('could not be built');
    }

    public function test_get_preview_for_saved_articles_still_works_and_is_noindex(): void
    {
        $author = User::factory()->create();
        $article = Article::factory()->draft()->for($author, 'author')->create();

        $this->actingAs($author)->get("/admin/articles/{$article->slug}/preview")
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('draft', false);
    }
}

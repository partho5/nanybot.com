<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminArticleListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_status_counts_cover_every_tab(): void
    {
        $admin = User::factory()->admin()->create();
        Article::factory()->count(3)->published()->create();
        Article::factory()->count(2)->draft()->create();
        Article::factory()->scheduled()->create();

        $this->actingAs($admin)->get('/admin/articles')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/articles/index')
                ->where('counts', ['all' => 6, 'draft' => 2, 'scheduled' => 1, 'published' => 3])
                ->has('articles.data', 6));
    }

    public function test_counts_respect_other_filters_but_not_status(): void
    {
        $admin = User::factory()->admin()->create();
        $news = Category::factory()->create();
        Article::factory()->count(2)->published()->for($news)->create();
        Article::factory()->draft()->for($news)->create();
        Article::factory()->count(4)->published()->create();

        $this->actingAs($admin)->get("/admin/articles?category={$news->id}&status=draft")
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts', ['all' => 3, 'draft' => 1, 'scheduled' => 0, 'published' => 2])
                ->has('articles.data', 1)
                ->where('articles.data.0.status', 'draft'));
    }

    public function test_counts_use_a_single_grouped_query(): void
    {
        $admin = User::factory()->admin()->create();
        Article::factory()->count(2)->published()->create();
        Article::factory()->draft()->create();

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/admin/articles')->assertOk();
        $grouped = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn ($sql) => str_contains($sql, 'group by "status"'));

        $this->assertCount(1, $grouped);
    }

    public function test_authors_only_see_and_count_their_own_articles(): void
    {
        $author = User::factory()->author()->create();
        Article::factory()->published()->for($author, 'author')->create();
        Article::factory()->draft()->for($author, 'author')->create();
        Article::factory()->count(5)->published()->create();

        $this->actingAs($author)->get('/admin/articles')
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts', ['all' => 2, 'draft' => 1, 'scheduled' => 0, 'published' => 1])
                ->has('articles.data', 2));
    }

    public function test_rows_carry_thumbnail_and_preview_urls(): void
    {
        $admin = User::factory()->admin()->create();
        $withImage = Article::factory()->published()->create([
            'featured_image' => 'https://res.cloudinary.com/demo/image/upload/v1/blog/cover.jpg',
            'updated_at' => now(),
        ]);
        Article::factory()->draft()->create(['updated_at' => now()->subDay()]);

        $this->actingAs($admin)->get('/admin/articles')
            ->assertInertia(fn (Assert $page) => $page
                ->where('articles.data.0.thumbnail_url', 'https://res.cloudinary.com/demo/image/upload/f_auto,q_auto,c_fill,ar_16:10,g_auto,w_128/v1/blog/cover.jpg')
                ->where('articles.data.0.preview_url', route('admin.articles.preview', $withImage))
                ->where('articles.data.0.public_url', $withImage->url())
                ->where('articles.data.1.thumbnail_url', null)
                ->where('articles.data.1.public_url', null));
    }
}

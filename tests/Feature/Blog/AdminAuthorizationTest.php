<?php

namespace Tests\Feature\Blog;

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Models\ApiKey;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $author;

    private User $otherAuthor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->admin = User::factory()->admin()->create();
        $this->author = User::factory()->author()->create();
        $this->otherAuthor = User::factory()->author()->create();
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function adminUris(): array
    {
        return [
            'dashboard' => ['get', '/dashboard'],
            'admin root' => ['get', '/admin'],
            'articles' => ['get', '/admin/articles'],
            'new article' => ['get', '/admin/articles/create'],
            'categories' => ['get', '/admin/categories'],
            'tags' => ['get', '/admin/tags'],
            'users' => ['get', '/admin/users'],
            'api keys' => ['get', '/admin/api-keys'],
            'tag autocomplete' => ['get', '/admin/articles/tags'],
            'store article' => ['post', '/admin/articles'],
            'store category' => ['post', '/admin/categories'],
            'store api key' => ['post', '/admin/api-keys'],
        ];
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function adminOnlyUris(): array
    {
        return [
            'categories' => ['get', '/admin/categories'],
            'tags' => ['get', '/admin/tags'],
            'users' => ['get', '/admin/users'],
            'api keys' => ['get', '/admin/api-keys'],
            'store category' => ['post', '/admin/categories'],
            'store tag' => ['post', '/admin/tags'],
            'store user' => ['post', '/admin/users'],
            'store api key' => ['post', '/admin/api-keys'],
        ];
    }

    #[DataProvider('adminUris')]
    public function test_guests_are_redirected_to_login(string $method, string $uri): void
    {
        $this->{$method}($uri)->assertRedirect(route('login'));
    }

    public function test_admin_root_redirects_signed_in_users_to_articles(): void
    {
        $this->actingAs($this->author)->get('/admin')->assertRedirect(route('admin.articles.index'));
    }

    #[DataProvider('adminOnlyUris')]
    public function test_authors_cannot_reach_admin_only_pages(string $method, string $uri): void
    {
        $this->actingAs($this->author)->{$method}($uri)->assertForbidden();
    }

    public function test_authors_cannot_delete_categories_tags_users_or_revoke_keys(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        [$key] = ApiKey::generate('k', $this->admin);

        $this->actingAs($this->author);
        $this->delete("/admin/categories/{$category->slug}")->assertForbidden();
        $this->delete("/admin/tags/{$tag->slug}")->assertForbidden();
        $this->delete("/admin/users/{$this->otherAuthor->id}")->assertForbidden();
        $this->delete("/admin/api-keys/{$key->id}")->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
        $this->assertDatabaseHas('users', ['id' => $this->otherAuthor->id]);
        $this->assertNull($key->fresh()->revoked_at);
    }

    public function test_admin_can_open_every_admin_page(): void
    {
        $this->actingAs($this->admin);

        $this->get('/dashboard')->assertRedirect(route('admin.articles.index'));

        foreach (['/admin/articles', '/admin/articles/create', '/admin/categories', '/admin/tags', '/admin/users', '/admin/api-keys'] as $uri) {
            $this->get($uri)->assertOk();
        }

        $this->get('/admin/articles')->assertInertia(fn (Assert $page) => $page->component('admin/articles/index')->where('isAdmin', true));
        $this->get('/admin/users')->assertInertia(fn (Assert $page) => $page->component('admin/users'));
        $this->get('/admin/api-keys')->assertInertia(fn (Assert $page) => $page->component('admin/api-keys'));
    }

    public function test_author_sees_the_article_screens_and_only_their_own_articles(): void
    {
        $mine = Article::factory()->create(['author_id' => $this->author->id, 'title' => 'Mine Alone']);
        Article::factory()->create(['author_id' => $this->otherAuthor->id, 'title' => 'Not Mine']);

        $this->actingAs($this->author)->get('/admin/articles/create')->assertOk();

        $this->get('/admin/articles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/articles/index')
                ->where('isAdmin', false)
                ->has('articles.data', 1)
                ->where('articles.data.0.id', $mine->id)
                ->where('authors', []));

        $this->get('/admin/articles/tags')->assertOk()->assertJsonStructure(['tags']);
    }

    public function test_admin_sees_every_article(): void
    {
        Article::factory()->create(['author_id' => $this->author->id]);
        Article::factory()->create(['author_id' => $this->otherAuthor->id]);

        $this->actingAs($this->admin)->get('/admin/articles')
            ->assertInertia(fn (Assert $page) => $page->has('articles.data', 2));
    }

    public function test_author_cannot_view_edit_update_preview_or_delete_other_authors_articles(): void
    {
        $theirs = Article::factory()->create(['author_id' => $this->otherAuthor->id, 'title' => 'Original']);

        $this->actingAs($this->author);
        $this->get("/admin/articles/{$theirs->slug}/edit")->assertForbidden();
        $this->get("/admin/articles/{$theirs->slug}/preview")->assertForbidden();
        $this->put("/admin/articles/{$theirs->slug}", [
            'title' => 'Hijacked', 'body' => '<p>x</p>', 'status' => 'published',
        ])->assertForbidden();
        $this->delete("/admin/articles/{$theirs->slug}")->assertForbidden();

        $theirs->refresh();
        $this->assertSame('Original', $theirs->title);
        $this->assertDatabaseHas('articles', ['id' => $theirs->id]);
    }

    public function test_author_can_edit_update_and_delete_their_own_article(): void
    {
        $mine = Article::factory()->create(['author_id' => $this->author->id, 'title' => 'Before']);

        $this->actingAs($this->author);
        $this->get("/admin/articles/{$mine->slug}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/articles/edit')->where('article.id', $mine->id));
        $this->get("/admin/articles/{$mine->slug}/preview")->assertOk();

        $this->put("/admin/articles/{$mine->slug}", [
            'title' => 'After', 'body' => '<p>Updated body that is fine.</p>', 'status' => 'published',
        ])->assertRedirect();
        $this->assertSame('After', $mine->fresh()->title);

        $this->delete('/admin/articles/'.$mine->fresh()->slug)->assertRedirect(route('admin.articles.index'));
        $this->assertDatabaseMissing('articles', ['id' => $mine->id]);
    }

    public function test_admin_can_edit_any_article_and_reassign_the_author(): void
    {
        $theirs = Article::factory()->create(['author_id' => $this->author->id]);

        $this->actingAs($this->admin)->put("/admin/articles/{$theirs->slug}", [
            'title' => 'Admin edit', 'body' => '<p>Body.</p>', 'status' => 'published', 'author_id' => $this->otherAuthor->id,
        ])->assertRedirect();

        $this->assertSame($this->otherAuthor->id, $theirs->fresh()->author_id);
    }

    public function test_authors_cannot_reassign_authorship_when_creating_or_updating(): void
    {
        $this->actingAs($this->author)->post('/admin/articles', [
            'title' => 'Claimed', 'body' => '<p>Body text.</p>', 'status' => 'draft', 'author_id' => $this->otherAuthor->id,
        ])->assertRedirect();

        $article = Article::where('title', 'Claimed')->firstOrFail();
        $this->assertSame($this->author->id, $article->author_id);
        $this->assertSame(ArticleStatus::Draft, $article->status);

        $this->put("/admin/articles/{$article->slug}", [
            'title' => 'Claimed', 'body' => '<p>Body text.</p>', 'status' => 'draft', 'author_id' => $this->otherAuthor->id,
        ])->assertRedirect();
        $this->assertSame($this->author->id, $article->fresh()->author_id);
    }

    public function test_authors_can_edit_only_their_own_profile(): void
    {
        $this->actingAs($this->author);

        $this->get("/admin/users/{$this->author->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/users/edit')->where('isSelf', true)->where('isAdmin', false));
        $this->get("/admin/users/{$this->otherAuthor->id}/edit")->assertForbidden();

        $this->put("/admin/users/{$this->otherAuthor->id}", [
            'name' => 'Hijacked', 'username' => 'hijacked', 'email' => 'h@example.com',
        ])->assertForbidden();
        $this->assertNotSame('Hijacked', $this->otherAuthor->fresh()->name);
    }

    public function test_author_cannot_escalate_their_own_role(): void
    {
        $this->actingAs($this->author)->put("/admin/users/{$this->author->id}", [
            'name' => $this->author->name,
            'username' => $this->author->username,
            'email' => $this->author->email,
            'role' => 'admin',
        ]);

        $this->assertSame(UserRole::Author, $this->author->fresh()->role);
    }

    public function test_admin_can_manage_api_keys_and_the_plaintext_is_shown_once(): void
    {
        $this->actingAs($this->admin)->post('/admin/api-keys', ['name' => 'CI', 'user_id' => $this->author->id])
            ->assertRedirect(route('admin.api-keys.index'));

        $key = ApiKey::where('name', 'CI')->firstOrFail();
        $this->assertSame($this->author->id, $key->user_id);
        $this->assertSame(64, strlen($key->token_hash));

        $this->delete("/admin/api-keys/{$key->id}")->assertRedirect();
        $this->assertNotNull($key->fresh()->revoked_at);
    }

    public function test_admin_cannot_delete_themselves_or_users_who_still_have_articles(): void
    {
        Article::factory()->create(['author_id' => $this->author->id]);

        $this->actingAs($this->admin);
        $this->delete("/admin/users/{$this->admin->id}")->assertRedirect(route('admin.users.index'));
        $this->delete("/admin/users/{$this->author->id}")->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
        $this->assertDatabaseHas('users', ['id' => $this->author->id]);

        $this->delete("/admin/users/{$this->otherAuthor->id}")->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $this->otherAuthor->id]);
    }

    public function test_slug_validation_rejects_reserved_and_duplicate_slugs(): void
    {
        $existing = Article::factory()->create();

        $this->actingAs($this->admin)->post('/admin/articles', [
            'title' => 'X', 'body' => '<p>b</p>', 'status' => 'draft', 'slug' => 'search',
        ])->assertSessionHasErrors('slug');

        $this->post('/admin/articles', [
            'title' => 'X', 'body' => '<p>b</p>', 'status' => 'draft', 'slug' => $existing->slug,
        ])->assertSessionHasErrors('slug');
    }

    public function test_reserved_slugs_have_a_single_source_of_truth(): void
    {
        $this->assertSame(Article::RESERVED_SLUGS, config('blog.reserved_slugs'));
    }

    public function test_social_link_labels_are_constrained(): void
    {
        $payload = fn (array $links) => [
            'name' => $this->author->name, 'email' => $this->author->email, 'social_links' => $links,
        ];

        $this->actingAs($this->author)
            ->put("/admin/users/{$this->author->id}", $payload(['<script>' => 'https://example.com']))
            ->assertSessionHasErrors('social_links');

        $this->put("/admin/users/{$this->author->id}", $payload([str_repeat('a', 31) => 'https://example.com']))
            ->assertSessionHasErrors('social_links');

        $this->put("/admin/users/{$this->author->id}", $payload(['GitHub' => 'https://github.com/x']))
            ->assertSessionHasNoErrors();
        $this->assertSame(['GitHub' => 'https://github.com/x'], $this->author->fresh()->social_links);
    }

    public function test_user_email_must_stay_unique(): void
    {
        $this->actingAs($this->author)
            ->put("/admin/users/{$this->author->id}", ['name' => $this->author->name, 'email' => $this->otherAuthor->email])
            ->assertSessionHasErrors('email');
    }

    public function test_scheduled_status_requires_a_publish_date(): void
    {
        $this->actingAs($this->admin)->post('/admin/articles', [
            'title' => 'X', 'body' => '<p>b</p>', 'status' => 'scheduled',
        ])->assertSessionHasErrors('publish_at');
    }
}

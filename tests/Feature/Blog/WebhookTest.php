<?php

namespace Tests\Feature\Blog;

use App\Enums\ArticleStatus;
use App\Jobs\ImportFeaturedImage;
use App\Models\ApiKey;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private string $token;

    private ApiKey $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create();
        [$this->key, $this->token] = ApiKey::generate('Test key', $this->owner);
    }

    /** @param array<string, mixed> $data */
    private function hook(string $uri, array $data = [], ?string $token = null): TestResponse
    {
        return $this->withToken($token ?? $this->token)->postJson($uri, $data);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'external_id' => 'ext-1',
            'title' => 'Hello from the webhook',
            'body' => '<p>This is a perfectly ordinary paragraph with enough words to be an excerpt.</p>',
            'status' => 'published',
        ], $overrides);
    }

    // --- Authentication -------------------------------------------------

    public function test_missing_key_is_rejected(): void
    {
        $this->postJson('/api/v1/articles', $this->payload())
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer');

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_bad_key_is_rejected(): void
    {
        $this->hook('/api/v1/articles', $this->payload(), 'nb_not-a-real-key')->assertUnauthorized();
    }

    public function test_revoked_key_is_rejected(): void
    {
        $this->key->update(['revoked_at' => now()]);

        $this->hook('/api/v1/articles', $this->payload())->assertUnauthorized();
    }

    public function test_failed_auth_attempts_are_throttled_per_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->hook('/api/v1/articles', $this->payload(), 'nb_wrong')->assertUnauthorized();
        }

        $this->hook('/api/v1/articles', $this->payload(), 'nb_wrong')
            ->assertStatus(429)
            ->assertHeader('Retry-After');

        // Even a valid key is locked out from that IP until the window passes.
        $this->hook('/api/v1/articles', $this->payload())->assertStatus(429);
    }

    public function test_successful_auth_records_last_used_at(): void
    {
        $this->hook('/api/v1/articles', $this->payload())->assertCreated();

        $this->assertNotNull($this->key->fresh()->last_used_at);
    }

    // --- Create / upsert -----------------------------------------------

    public function test_creates_an_article_with_201_then_upserts_with_200(): void
    {
        $created = $this->hook('/api/v1/articles', $this->payload())
            ->assertCreated()
            ->assertJsonPath('external_id', 'ext-1')
            ->assertJsonPath('status', 'published');

        $article = Article::findOrFail($created->json('id'));
        $this->assertSame($this->key->id, $article->api_key_id);
        $this->assertSame($this->owner->id, $article->author_id);
        $this->assertSame($article->url(), $created->json('url'));
        $this->assertNotEmpty($article->body_html);

        $this->hook('/api/v1/articles', $this->payload(['title' => 'Edited title']))
            ->assertOk()
            ->assertJsonPath('id', $article->id);

        $this->assertDatabaseCount('articles', 1);
        $this->assertSame('Edited title', $article->fresh()->title);
    }

    public function test_put_updates_an_existing_article_by_external_id(): void
    {
        $id = $this->hook('/api/v1/articles', $this->payload())->json('id');

        $this->withToken($this->token)
            ->putJson('/api/v1/articles/ext-1', ['title' => 'Via PUT'])
            ->assertOk()
            ->assertJsonPath('id', $id);

        $article = Article::find($id);
        $this->assertSame('Via PUT', $article->title);
        $this->assertStringContainsString('ordinary paragraph', $article->body_html);
    }

    public function test_two_keys_can_reuse_the_same_external_id(): void
    {
        [, $otherToken] = ApiKey::generate('Other', User::factory()->create());

        $a = $this->hook('/api/v1/articles', $this->payload(['title' => 'From key A']))->assertCreated();
        $b = $this->hook('/api/v1/articles', $this->payload(['title' => 'From key B']), $otherToken)->assertCreated();

        $this->assertNotSame($a->json('id'), $b->json('id'));
        $this->assertNotSame($a->json('url'), $b->json('url'));
        $this->assertDatabaseCount('articles', 2);
        $this->assertSame('From key A', Article::find($a->json('id'))->title);
    }

    public function test_validation_requires_title_and_body_for_new_articles(): void
    {
        $this->hook('/api/v1/articles', ['external_id' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body']);
    }

    public function test_unknown_author_is_rejected(): void
    {
        $this->hook('/api/v1/articles', $this->payload(['author' => 'nobody-here']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('author');

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_author_can_be_chosen_by_username_or_email(): void
    {
        $author = User::factory()->create(['username' => 'jane-doe', 'email' => 'Jane@example.com']);

        $this->hook('/api/v1/articles', $this->payload(['author' => 'jane-doe']))->assertCreated();
        $this->hook('/api/v1/articles', $this->payload(['external_id' => 'ext-2', 'title' => 'Second', 'author' => 'jane@example.com']))->assertCreated();

        $this->assertSame(2, Article::where('author_id', $author->id)->count());
    }

    public function test_category_and_tags_are_created_and_attached(): void
    {
        $id = $this->hook('/api/v1/articles', $this->payload([
            'category' => 'Growth Hacks',
            'tags' => ['Alpha', 'Beta'],
        ]))->assertCreated()->json('id');

        $article = Article::with('category', 'tags')->find($id);
        $this->assertSame('growth-hacks', $article->category->slug);
        $this->assertEqualsCanonicalizing(['Alpha', 'Beta'], $article->tags->pluck('name')->all());
    }

    // --- Delete --------------------------------------------------------

    public function test_delete_is_scoped_to_the_calling_key(): void
    {
        [, $otherToken] = ApiKey::generate('Other', User::factory()->create());
        $id = $this->hook('/api/v1/articles', $this->payload())->json('id');

        $this->withToken($otherToken)->deleteJson('/api/v1/articles/ext-1')->assertNotFound();
        $this->assertDatabaseHas('articles', ['id' => $id]);

        $this->withToken($this->token)->deleteJson('/api/v1/articles/ext-1')
            ->assertOk()
            ->assertJsonPath('deleted', true);
        $this->assertDatabaseMissing('articles', ['id' => $id]);
    }

    public function test_put_for_another_keys_external_id_does_not_touch_it(): void
    {
        [, $otherToken] = ApiKey::generate('Other', User::factory()->create());
        $id = $this->hook('/api/v1/articles', $this->payload())->json('id');

        // Unknown to the other key, so it's a create attempt without title/body.
        $this->withToken($otherToken)->putJson('/api/v1/articles/ext-1', ['title' => 'Hijack'])
            ->assertUnprocessable();

        $this->assertNotSame('Hijack', Article::find($id)->title);
    }

    // --- Scheduling ----------------------------------------------------

    public function test_future_publish_at_schedules_the_article_until_the_command_runs(): void
    {
        $response = $this->hook('/api/v1/articles', $this->payload(['status' => null, 'publish_at' => now()->addDay()->toIso8601String()]))
            ->assertCreated()
            ->assertJsonPath('status', 'scheduled');

        $article = Article::findOrFail($response->json('id'));
        $this->assertSame(ArticleStatus::Scheduled, $article->status);
        $this->get("/blog/{$article->slug}")->assertNotFound();
        $this->assertCount(0, Article::published()->get());

        // Time passes.
        $article->forceFill(['published_at' => now()->subMinute()])->saveQuietly();

        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $this->assertSame(ArticleStatus::Published, $article->fresh()->status);
        $this->get("/blog/{$article->slug}")->assertOk();
    }

    public function test_past_publish_at_publishes_immediately(): void
    {
        $this->hook('/api/v1/articles', $this->payload(['status' => null, 'publish_at' => now()->subDay()->toIso8601String()]))
            ->assertCreated()
            ->assertJsonPath('status', 'published');
    }

    public function test_new_articles_without_status_are_drafts_and_hidden(): void
    {
        $response = $this->hook('/api/v1/articles', $this->payload(['status' => null]))
            ->assertCreated()
            ->assertJsonPath('status', 'draft');

        $this->get('/blog/'.Article::find($response->json('id'))->slug)->assertNotFound();
    }

    // --- Featured image ------------------------------------------------

    /** @return array<string, array{0: string}> */
    public static function unsafeImageUrls(): array
    {
        return [
            'loopback' => ['http://127.0.0.1/pixel.png'],
            'localhost' => ['http://localhost/pixel.png'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'private range' => ['http://10.0.0.5/a.png'],
            'ipv6 loopback' => ['http://[::1]/a.png'],
            'odd port' => ['http://example.com:8080/a.png'],
            'credentials' => ['http://user:pass@example.com/a.png'],
            'decimal ip' => ['http://2130706433/a.png'],
            'wrong scheme' => ['ftp://example.com/a.png'],
        ];
    }

    #[DataProvider('unsafeImageUrls')]
    public function test_ssrf_unsafe_featured_image_urls_are_rejected(string $url): void
    {
        Queue::fake();

        $this->hook('/api/v1/articles', $this->payload(['featured_image_url' => $url]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('featured_image_url');

        $this->assertDatabaseCount('articles', 0);
        Queue::assertNothingPushed();
    }

    public function test_non_cloudinary_image_is_queued_for_import(): void
    {
        Queue::fake();

        $id = $this->hook('/api/v1/articles', $this->payload(['featured_image_url' => 'https://example.com/photos/cat.jpg']))
            ->assertCreated()->json('id');

        Queue::assertPushed(ImportFeaturedImage::class, fn ($job) => $job->articleId === $id && $job->sourceUrl === 'https://example.com/photos/cat.jpg');
        $this->assertSame('https://example.com/photos/cat.jpg', Article::find($id)->featured_image);
    }

    public function test_cloudinary_image_is_stored_without_an_import_job(): void
    {
        Queue::fake();
        $url = 'https://res.cloudinary.com/demo/image/upload/v1/blog/cat.jpg';

        $id = $this->hook('/api/v1/articles', $this->payload(['featured_image_url' => $url]))->assertCreated()->json('id');

        Queue::assertNothingPushed();
        $this->assertSame($url, Article::find($id)->featured_image);
    }

    public function test_resending_the_same_image_url_does_not_requeue_the_import(): void
    {
        Queue::fake();
        $payload = $this->payload(['featured_image_url' => 'https://example.com/photos/cat.jpg']);

        $this->hook('/api/v1/articles', $payload)->assertCreated();
        $this->hook('/api/v1/articles', $payload)->assertOk();

        Queue::assertPushed(ImportFeaturedImage::class, 1);
    }
}

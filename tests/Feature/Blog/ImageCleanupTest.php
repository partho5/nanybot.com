<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use App\Models\User;
use App\Services\Blog\ArticleWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImageCleanupTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://res.cloudinary.com/demo/image/upload/v1/nanybot/';

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.cloudinary' => [
            'cloud_name' => 'demo', 'api_key' => 'k', 'api_secret' => 's', 'upload_preset' => 'p', 'folder' => 'nanybot',
        ]]);
        Http::fake();

        $this->author = User::factory()->author()->create();
    }

    private function img(string $name): string
    {
        return '<img src="'.self::BASE.$name.'.jpg" alt="x" width="10" height="10">';
    }

    private function save(?Article $article, string $body, array $extra = []): Article
    {
        return app(ArticleWriter::class)->save($article ?? new Article, [
            'author_id' => $this->author->id,
            'title' => 'T',
            'body' => $body,
            'status' => 'draft',
            ...$extra,
        ]);
    }

    /** @return list<string> public ids sent to Cloudinary's destroy endpoint */
    private function destroyed(): array
    {
        return Http::recorded()
            ->filter(fn (array $pair) => str_contains($pair[0]->url(), '/image/destroy'))
            ->map(fn (array $pair) => $pair[0]->data()['public_id'])
            ->values()
            ->all();
    }

    public function test_image_removed_from_the_body_is_deleted_on_save(): void
    {
        $article = $this->save(null, '<p>a</p>'.$this->img('keep').$this->img('gone'));
        $this->assertSame([], $this->destroyed());

        $this->save($article, '<p>a</p>'.$this->img('keep'));

        $this->assertSame(['nanybot/gone'], $this->destroyed());
    }

    public function test_image_still_used_by_another_article_is_kept(): void
    {
        $this->save(null, $this->img('shared'));
        $article = $this->save(null, $this->img('shared'));

        $this->save($article, '<p>no image now</p>');

        $this->assertSame([], $this->destroyed());
    }

    public function test_uploaded_then_removed_images_are_deleted_but_used_ones_are_not(): void
    {
        $article = $this->save(null, '<p>a</p>', [
            'uploaded_images' => [self::BASE.'never-used.jpg', self::BASE.'used.jpg'],
        ]);
        $this->assertSame(['nanybot/never-used', 'nanybot/used'], $this->destroyed());

        Http::fake();
        $this->save($article, $this->img('used'), ['uploaded_images' => [self::BASE.'used.jpg']]);

        $this->assertSame([], $this->destroyed());
    }

    public function test_images_outside_our_cloud_are_never_deleted(): void
    {
        $this->save(null, '<p>a</p>', ['uploaded_images' => ['https://res.cloudinary.com/other/image/upload/v1/x.jpg', 'https://example.com/a.jpg']]);

        $this->assertSame([], $this->destroyed());
    }

    public function test_deleting_an_article_deletes_its_body_images(): void
    {
        $article = $this->save(null, $this->img('one').$this->img('two'));

        $article->delete();

        $this->assertEqualsCanonicalizing(['nanybot/one', 'nanybot/two'], $this->destroyed());
    }

    public function test_replacing_an_avatar_deletes_the_old_one(): void
    {
        $user = User::factory()->admin()->create(['avatar' => self::BASE.'old.jpg']);

        $this->actingAs($user)->put(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'admin',
            'avatar' => self::BASE.'new.jpg',
        ])->assertRedirect();

        $this->assertSame(['nanybot/old'], $this->destroyed());
        $this->assertSame(self::BASE.'new.jpg', $user->fresh()->avatar);
    }

    public function test_save_request_accepts_uploaded_images(): void
    {
        $this->actingAs($this->author)->post(route('admin.articles.store'), [
            'title' => 'T', 'body' => '<p>x</p>', 'status' => 'draft',
            'uploaded_images' => [self::BASE.'orphan.jpg'],
        ])->assertRedirect();

        $this->assertSame(['nanybot/orphan'], $this->destroyed());
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/demo/image/destroy'));
    }
}

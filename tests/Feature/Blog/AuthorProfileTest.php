<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use App\Models\User;
use App\Support\SocialNetworks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->author = User::factory()->admin()->create([
            'username' => 'nanybot-admin-uyqr',
            'name' => 'NanyBot Admin',
            'bio' => 'Builds automation for small businesses.',
            'social_links' => ['DEV' => 'https://dev.to/ada', 'Stack Overflow' => 'https://stackoverflow.com/users/1/ada'],
        ]);
        Article::factory()->published()->create(['author_id' => $this->author->id]);
    }

    /** @return array<string, mixed> */
    private function profile(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->author->name,
            'email' => $this->author->email,
            'username' => $this->author->username,
            'role' => 'admin',
        ], $overrides);
    }

    public function test_renaming_the_username_redirects_the_old_author_url(): void
    {
        $this->actingAs($this->author)
            ->put(route('admin.users.update', $this->author), $this->profile(['username' => 'partho']))
            ->assertRedirect();

        $this->get('/blog/author/nanybot-admin-uyqr')->assertRedirect('/blog/author/partho')->assertStatus(301);
        $this->get('/blog/author/partho')->assertOk();
    }

    public function test_changing_only_the_display_name_keeps_the_author_url(): void
    {
        $this->actingAs($this->author)
            ->put(route('admin.users.update', $this->author), $this->profile(['name' => 'Partho Protim']))
            ->assertRedirect();

        $this->get('/blog/author/nanybot-admin-uyqr')->assertOk()->assertSee('Partho Protim');
    }

    public function test_author_with_no_published_articles_is_a_404(): void
    {
        $this->author->articles()->delete();

        $this->get('/blog/author/nanybot-admin-uyqr')->assertNotFound();
    }

    public function test_author_page_shows_bio_and_social_pills_with_icons(): void
    {
        $html = $this->get('/blog/author/nanybot-admin-uyqr')->assertOk()->getContent();

        $this->assertStringContainsString('Builds automation for small businesses.', $html);
        $this->assertStringContainsString('href="https://dev.to/ada"', $html);
        $this->assertStringContainsString('Stack Overflow', $html);
        $this->assertStringContainsString(SocialNetworks::path('devto'), $html);
        $this->assertStringContainsString(SocialNetworks::path('stackoverflow'), $html);
    }

    public function test_article_page_card_is_titled_about_the_author(): void
    {
        $article = $this->author->articles()->first();

        $this->get($article->url())->assertOk()->assertSee('About the author')->assertSee('Builds automation for small businesses.');
    }

    public function test_social_networks_are_detected_from_the_host(): void
    {
        $this->assertSame('devto', SocialNetworks::detect('https://dev.to/ada'));
        $this->assertSame('github', SocialNetworks::detect('https://www.github.com/ada'));
        $this->assertSame('medium', SocialNetworks::detect('https://ada.medium.com'));
        $this->assertSame('x', SocialNetworks::detect('https://twitter.com/ada'));
        $this->assertSame('globe', SocialNetworks::detect('https://ada.example'));
        $this->assertSame('globe', SocialNetworks::detect('https://notdev.to.evil.example'));
    }

    public function test_edit_form_receives_the_network_registry(): void
    {
        $this->actingAs($this->author)->get(route('admin.users.edit', $this->author))
            ->assertInertia(fn ($page) => $page->has('socialNetworks', fn ($n) => $n->etc())->where('socialNetworks.0.key', 'x'));
    }
}

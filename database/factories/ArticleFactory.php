<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Inserts rows directly (no ArticleWriter), so body_html is only a basic copy
 * of body. Use ArticleWriter when processed HTML, TOC or FAQ matter.
 *
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(rand(4, 8)), '.');
        $paragraphs = fake()->paragraphs(rand(3, 5));
        $body = '<h2>'.fake()->sentence(3).'</h2>'
            .collect($paragraphs)->map(fn ($p) => "<p>{$p}</p>")->implode('');
        $words = str_word_count(strip_tags($body));

        return [
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
            'api_key_id' => null,
            'external_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'excerpt' => Str::limit($paragraphs[0], 200, '…'),
            'meta_title' => null,
            'meta_description' => null,
            'body' => $body,
            'body_html' => $body,
            'toc' => null,
            'faq' => null,
            'featured_image' => null,
            'featured_image_alt' => null,
            'featured_image_caption' => null,
            'status' => ArticleStatus::Published,
            'read_time_minutes' => max(1, (int) ceil($words / 200)),
            'word_count' => $words,
            'published_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::Published,
            'published_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ArticleStatus::Draft, 'published_at' => null]);
    }

    public function scheduled(?\DateTimeInterface $at = null): static
    {
        return $this->state(fn () => [
            'status' => ArticleStatus::Scheduled,
            'published_at' => $at ?? now()->addDays(3),
        ]);
    }

    /** A Cloudinary-style featured image (no network access involved). */
    public function withImage(string $caption = 'Featured image'): static
    {
        return $this->state(fn () => [
            'featured_image' => 'https://res.cloudinary.com/demo/image/upload/v1/blog/'.Str::lower(Str::random(10)).'.jpg',
            'featured_image_alt' => 'Placeholder featured image',
            'featured_image_caption' => $caption,
        ]);
    }
}

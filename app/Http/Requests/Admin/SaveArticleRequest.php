<?php

namespace App\Http\Requests\Admin;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create and update share the same rules; on update the route carries the
 * {article} model, which the slug uniqueness check ignores.
 */
class SaveArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article
            ? Gate::allows('update', $article)
            : Gate::allows('create', Article::class);
    }

    protected function prepareForValidation(): void
    {
        // Accept a comma-separated tag string as well as an array.
        if (is_string($this->tags)) {
            $this->merge([
                'tags' => array_values(array_filter(array_map('trim', explode(',', $this->tags)))),
            ]);
        }

        // Empty strings from the form mean "not set".
        foreach (['slug', 'publish_at', 'category_id', 'author_id'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $article = $this->route('article');

        return [
            'title' => ['required', 'string', 'max:255'],
            // Optional: ArticleWriter derives a unique slug from the title.
            'slug' => [
                'nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(Article::RESERVED_SLUGS),
                Rule::unique('articles', 'slug')->ignore($article instanceof Article ? $article->id : null),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'featured_image' => ['nullable', 'url:https', 'max:2048'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'featured_image_caption' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:60'],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'publish_at' => ['required_if:status,scheduled', 'nullable', 'date'],
            // Only admins may pick the author; ignored for everyone else.
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'slug.not_in' => 'This slug is reserved by the blog ('.implode(', ', Article::RESERVED_SLUGS).').',
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens only.',
        ];
    }
}

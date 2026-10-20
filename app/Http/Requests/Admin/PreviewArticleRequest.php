<?php

namespace App\Http\Requests\Admin;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The editor's unsaved-preview payload. Same limits as SaveArticleRequest but
 * deliberately lenient (nothing is required, the slug need not be unique):
 * a half-written article must still be previewable. Authorization mirrors
 * editing: update rights on the article being edited, or create rights for a new one.
 */
class PreviewArticleRequest extends FormRequest
{
    private ?Article $editing = null;

    public function authorize(): bool
    {
        $id = $this->input('article_id');

        if ($id === null || $id === '') {
            return Gate::allows('create', Article::class);
        }

        $this->editing = Article::query()->findOrFail((int) $id);

        return Gate::allows('update', $this->editing);
    }

    /** The saved article being edited, or null for a never-saved one. */
    public function editing(): ?Article
    {
        return $this->editing;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->tags)) {
            $this->merge([
                'tags' => array_values(array_filter(array_map('trim', explode(',', $this->tags)))),
            ]);
        }

        foreach (['slug', 'publish_at', 'category_id', 'author_id', 'article_id'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'article_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:200'],
            'body' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'url:https', 'max:2048'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'featured_image_caption' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:60'],
            'status' => ['nullable', Rule::enum(ArticleStatus::class)],
            'publish_at' => ['nullable', 'date'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /** This opens in a new tab, so a redirect-back would be useless: explain on the page instead. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response(
            "The preview could not be built:\n- ".$validator->errors()->all()[0]."\n\nGo back to the editor and fix the field.",
            422,
            ['Content-Type' => 'text/plain; charset=utf-8', 'X-Robots-Tag' => 'noindex'],
        ));
    }
}

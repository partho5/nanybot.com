<?php

namespace App\Http\Requests\Api;

use App\Enums\ArticleStatus;
use App\Models\ApiKey;
use App\Models\Article;
use App\Models\User;
use App\Support\PublicUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebhookArticleRequest extends FormRequest
{
    private ?User $resolvedAuthor = null;

    private bool $existingLoaded = false;

    private ?Article $existing = null;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isNew = $this->existingArticle() === null;
        $required = $isNew ? 'required' : 'sometimes';

        return [
            'external_id' => ['nullable', 'string', 'max:191'],
            'title' => [$required, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:200'],
            'body' => [$required, 'string', 'max:2000000'],
            'featured_image_url' => ['nullable', 'string', 'url:http,https', 'max:2048', function (string $attribute, mixed $value, \Closure $fail) {
                if (! PublicUrl::isSafe((string) $value)) {
                    $fail('The featured image URL must be a public http(s) address.');
                }
            }],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'featured_image_caption' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:60'],
            'author' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
                if (! $this->resolveAuthor((string) $value)) {
                    $fail('No author found with that username or email.');
                }
            }],
            'status' => ['nullable', Rule::enum(ArticleStatus::class)],
            'publish_at' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $this->input('status') === ArticleStatus::Scheduled->value),
            ],
        ];
    }

    /** The article this request targets for this key, if it already exists. */
    public function existingArticle(): ?Article
    {
        if (! $this->existingLoaded) {
            $this->existingLoaded = true;
            $externalId = $this->isMethod('POST') ? $this->input('external_id') : $this->route('externalId');
            $key = $this->attributes->get('api_key');

            $this->existing = is_string($externalId) && $externalId !== '' && $key instanceof ApiKey
                ? Article::where('api_key_id', $key->id)->where('external_id', $externalId)->first()
                : null;
        }

        return $this->existing;
    }

    /** The requested author, or null when none was sent (use the key's user). */
    public function author(): ?User
    {
        $value = $this->input('author');

        return filled($value) ? $this->resolveAuthor((string) $value) : null;
    }

    private function resolveAuthor(string $value): ?User
    {
        if ($this->resolvedAuthor?->username === $value || $this->resolvedAuthor?->email === $value) {
            return $this->resolvedAuthor;
        }

        $value = trim($value);

        return $this->resolvedAuthor = User::query()
            ->where('username', $value)
            ->orWhereRaw('lower(email) = ?', [mb_strtolower($value)])
            ->first();
    }
}

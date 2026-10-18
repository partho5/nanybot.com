<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebhookArticleRequest;
use App\Jobs\ImportFeaturedImage;
use App\Models\ApiKey;
use App\Models\Article;
use App\Models\Category;
use App\Services\Blog\ArticleWriter;
use App\Services\CloudinaryService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Article webhook. Articles are keyed by (api key, external_id), so a key can
 * only ever see and change what it created itself.
 */
class ArticleWebhookController extends Controller
{
    public function __construct(private ArticleWriter $writer, private CloudinaryService $cloudinary) {}

    public function store(WebhookArticleRequest $request): JsonResponse
    {
        return $this->upsert($request, $request->input('external_id') ?: null);
    }

    public function update(WebhookArticleRequest $request, string $externalId): JsonResponse
    {
        return $this->upsert($request, $externalId);
    }

    public function destroy(Request $request, string $externalId): JsonResponse
    {
        $article = Article::where('api_key_id', $this->key($request)->id)
            ->where('external_id', $externalId)
            ->first();

        if (! $article) {
            return response()->json(['message' => 'Article not found.'], 404);
        }

        $this->writer->delete($article);
        Cache::forget($this->imageSourceKey($article->id));

        return response()->json(['id' => $article->id, 'external_id' => $externalId, 'deleted' => true]);
    }

    private function upsert(WebhookArticleRequest $request, ?string $externalId, bool $retry = true): JsonResponse
    {
        $key = $this->key($request);
        $existing = $request->existingArticle();
        $article = $existing ?? new Article;
        $input = $request->validated();

        $data = Arr::only($input, ['title', 'excerpt', 'meta_title', 'meta_description', 'body', 'featured_image_alt', 'featured_image_caption']);

        if (! empty($input['slug'])) {
            $data['slug'] = $input['slug'];
        }

        if (array_key_exists('category', $input)) {
            $data['category_id'] = filled($input['category']) ? $this->resolveCategory($input['category'])->id : null;
        }

        if (array_key_exists('tags', $input)) {
            $data['tags'] = $input['tags'] ?? [];
        }

        [$status, $publishAt] = $this->resolveStatus($input, $existing === null);
        if ($status) {
            $data['status'] = $status;
        }
        if ($publishAt) {
            $data['publish_at'] = $publishAt;
        }

        if ($author = $request->author()) {
            $data['author_id'] = $author->id;
        } elseif (! $existing) {
            $data['author_id'] = $key->user_id;
        }

        if (! $existing) {
            $data['api_key_id'] = $key->id;
            $data['external_id'] = $externalId;
        }

        $importUrl = null;
        if (array_key_exists('featured_image_url', $input)) {
            $url = $input['featured_image_url'];
            $sourceKey = $existing ? Cache::get($this->imageSourceKey($existing->id)) : null;

            if (blank($url)) {
                $data['featured_image'] = null;
            } elseif ($url !== $existing?->featured_image && $url !== $sourceKey) {
                $data['featured_image'] = $url;
                $importUrl = $this->cloudinary->isCloudinaryUrl($url) ? null : $url;
            }
        }

        try {
            $article = $this->writer->save($article, $data);
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent retry created it first: turn this call into an update.
            if ($retry && ! $existing && $externalId !== null) {
                return $this->upsert($request, $externalId, retry: false);
            }

            throw $e;
        }

        if (array_key_exists('featured_image_url', $input)) {
            if ($importUrl) {
                // Remember the source so re-sending the same URL doesn't re-import it.
                Cache::forever($this->imageSourceKey($article->id), $importUrl);
                ImportFeaturedImage::dispatch($article->id, $importUrl);
            } elseif (blank($input['featured_image_url'])) {
                Cache::forget($this->imageSourceKey($article->id));
            }
        }

        return response()->json([
            'id' => $article->id,
            'external_id' => $article->external_id,
            'url' => $article->url(),
            'status' => $article->status->value,
            'published_at' => $article->published_at?->toIso8601String(),
        ], $existing ? 200 : 201);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: ?string, 1: ?Carbon}
     */
    private function resolveStatus(array $input, bool $isNew): array
    {
        $publishAt = filled($input['publish_at'] ?? null) ? Carbon::parse($input['publish_at']) : null;
        $status = filled($input['status'] ?? null) ? ArticleStatus::from($input['status']) : null;

        if ($status === null) {
            $status = $publishAt
                ? ($publishAt->isFuture() ? ArticleStatus::Scheduled : ArticleStatus::Published)
                : ($isNew ? ArticleStatus::Draft : null);
        } elseif ($publishAt && $status !== ArticleStatus::Draft) {
            $status = $publishAt->isFuture() ? ArticleStatus::Scheduled : ArticleStatus::Published;
        }

        return [$status?->value, $status === ArticleStatus::Draft ? null : $publishAt];
    }

    /** Match by slug, then case-insensitive name; create when missing. */
    private function resolveCategory(string $value): Category
    {
        $value = trim($value);
        $slug = Str::slug($value) ?: 'uncategorized';

        return Category::where('slug', $slug)->first()
            ?? Category::whereRaw('lower(name) = ?', [mb_strtolower($value)])->first()
            ?? Category::firstOrCreate(['slug' => $slug], ['name' => Str::limit($value, 255, '')]);
    }

    private function key(Request $request): ApiKey
    {
        return $request->attributes->get('api_key');
    }

    private function imageSourceKey(int $articleId): string
    {
        return "webhook:image-source:{$articleId}";
    }
}

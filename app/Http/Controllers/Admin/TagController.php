<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $tags = Tag::query()
            ->withCount('articles')
            ->when($q !== '', fn ($query) => $query->where('name', 'ilike', '%'.addcslashes($q, '%_\\').'%'))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Tag $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'description' => $t->description,
                'articles_count' => $t->articles_count,
            ]);

        return Inertia::render('admin/tags', [
            'tags' => $tags,
            'filters' => ['q' => $q],
        ]);
    }

    public function store(SaveTagRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);

        Tag::create($data);

        Inertia::flash('success', 'Tag created.');

        return back();
    }

    public function update(SaveTagRequest $request, Tag $tag): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        $tag->update($data);

        Inertia::flash('success', 'Tag updated.');

        return back();
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        // The pivot rows cascade; articles themselves are untouched.
        $tag->delete();

        Inertia::flash('success', 'Tag deleted.');

        return back();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name) ?: 'tag', 80, '');
        $slug = $base;
        $i = 2;

        while (Tag::where('slug', $slug)->exists()) {
            $slug = Str::limit($base, 75, '')."-{$i}";
            $i++;
        }

        return $slug;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/categories', [
            'categories' => Category::query()
                ->withCount('articles')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'description'])
                ->map(fn (Category $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'description' => $c->description,
                    'articles_count' => $c->articles_count,
                    'url' => $c->url(),
                ])
                ->all(),
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);

        Category::create($data);

        Inertia::flash('success', 'Category created.');

        return redirect()->route('admin.categories.index');
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        // Keep the existing slug (public URL) unless one is given explicitly.
        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        $category->update($data);

        Inertia::flash('success', 'Category updated.');

        return redirect()->route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Articles keep existing: the FK nulls their category.
        $category->delete();

        Inertia::flash('success', 'Category deleted.');

        return redirect()->route('admin.categories.index');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}

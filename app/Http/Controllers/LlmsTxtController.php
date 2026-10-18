<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Response;

/** llms.txt: a plain-text index of the site for LLM crawlers. */
class LlmsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $name = config('blog.name');

        $lines = [
            "# {$name}",
            '',
            '> '.config('blog.tagline'),
            '',
            '## Key Pages',
            '- Homepage: '.url('/'),
            '- Blog: '.route('blog.index'),
            '- RSS feed: '.route('blog.feed'),
            '- Sitemap: '.route('sitemap'),
        ];

        $categories = Category::query()
            ->whereHas('articles', fn ($q) => $q->published())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        if ($categories->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Categories';
            foreach ($categories as $category) {
                $lines[] = "- {$category->name}: ".$category->url();
            }
        }

        $articles = Article::query()
            ->published()
            ->with(['author:id,name', 'category:id,name', 'tags:id,name'])
            ->select(['id', 'author_id', 'category_id', 'title', 'slug', 'excerpt', 'meta_description', 'read_time_minutes', 'published_at'])
            ->orderByDesc('published_at')
            ->limit(500)
            ->get();

        if ($articles->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Articles';

            foreach ($articles as $article) {
                $desc = preg_replace('/\s+/', ' ', trim(strip_tags($article->excerpt ?: $article->meta_description ?: '')));
                $desc = $desc !== '' ? str($desc)->limit(200)->toString() : "A {$article->read_time_minutes}-minute read.";

                $lines[] = '';
                $lines[] = "### {$article->title}";
                $lines[] = '- URL: '.$article->url();
                $lines[] = '- Markdown: '.route('blog.markdown', $article);
                if ($article->author) {
                    $lines[] = "- Author: {$article->author->name}";
                }
                if ($article->category) {
                    $lines[] = "- Category: {$article->category->name}";
                }
                if ($article->tags->isNotEmpty()) {
                    $lines[] = '- Keywords: '.$article->tags->pluck('name')->implode(', ');
                }
                $lines[] = "- {$desc}";
            }
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}

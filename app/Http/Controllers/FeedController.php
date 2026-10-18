<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Response;

/** RSS 2.0 feed of the latest published articles. */
class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $articles = Article::query()
            ->published()
            ->with(['author:id,name,username', 'category:id,name', 'tags:id,name'])
            ->select(['id', 'author_id', 'category_id', 'title', 'slug', 'excerpt', 'body_html', 'featured_image', 'meta_description', 'published_at', 'updated_at'])
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        $xml = view('feed', [
            'articles' => $articles,
            'updated' => $articles->max('published_at'),
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=utf-8']);
    }
}

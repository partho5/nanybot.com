<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

/** Placeholder landing page — to be extended. */
class LandingController extends Controller
{
    public function __invoke(): View
    {
        $seo = Seo::make(config('blog.name'), config('blog.tagline'), route('home'));
        $latest = Article::published()->forListing()->latest('published_at')->limit(3)->get();

        return view('landing', compact('seo', 'latest'));
    }
}

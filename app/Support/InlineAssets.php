<?php

namespace App\Support;

use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

/**
 * Inline a built CSS entry into <head>: zero render-blocking requests on the
 * first (usually only) page view from search. Falls back to a <link> if the
 * file ever grows past the budget, and to Vite's dev server when running hot.
 */
class InlineAssets
{
    private const INLINE_BUDGET_BYTES = 60_000;

    /** @var array<string, string> */
    private static array $memo = [];

    public static function css(string $entry): HtmlString
    {
        $vite = app(Vite::class);

        if ($vite->isRunningHot()) {
            return $vite($entry);
        }

        if (! isset(self::$memo[$entry])) {
            $manifestPath = public_path('build/manifest.json');
            $manifest = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : [];
            $file = $manifest[$entry]['file'] ?? null;
            $path = $file ? public_path('build/'.$file) : null;

            if (! $path || ! is_file($path)) {
                return $vite($entry);
            }

            self::$memo[$entry] = filesize($path) > self::INLINE_BUDGET_BYTES
                ? '<link rel="stylesheet" href="'.e(asset('build/'.$file)).'">'
                : '<style>'.file_get_contents($path).'</style>';
        }

        return new HtmlString(self::$memo[$entry]);
    }
}

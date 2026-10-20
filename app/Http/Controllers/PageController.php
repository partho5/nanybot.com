<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Illuminate\Contracts\View\View;

/** Company pages (privacy, terms, refund, contact): static Blade, page-cached. */
class PageController extends Controller
{
    /** @var array<string, array{0: string, 1: string}> slug => [title, description] */
    public const PAGES = [
        'privacy' => ['Privacy Policy | Nanybot', 'How Nanybot handles personal data on nanybot.com and in messages sent to us. Product data is covered by each product\'s own policy.'],
        'terms' => ['Terms of Service | Nanybot', 'The terms for using nanybot.com and Nanybot software, including acceptable use, payments, automation responsibility and liability.'],
        'refund' => ['Refund Policy | Nanybot', 'Nanybot\'s 30-day refund policy for paid plans, what is covered, how to ask, and when the money returns.'],
        'contact' => ['Contact Nanybot | Support, billing and privacy', 'Contact Nanybot for product support, billing and refunds, privacy requests, or to suggest a task worth automating.'],
    ];

    public function __invoke(string $page): View
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        [$title, $description] = self::PAGES[$page];

        return view("pages.{$page}", ['seo' => Seo::make($title, $description, route($page))]);
    }
}

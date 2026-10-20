<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_pages_render_with_canonical_and_no_external_scripts(): void
    {
        foreach (['privacy', 'terms', 'refund', 'contact'] as $page) {
            $this->get(route($page))
                ->assertOk()
                ->assertSee('<link rel="canonical" href="'.route($page).'">', false)
                ->assertDontSee('cdn.tailwindcss.com');
        }
    }

    public function test_sitemap_lists_company_pages_and_the_laptop_solver_site(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('privacy'), false)
            ->assertSee(url('ai-laptop-solver/'), false)
            ->assertSee(url('ai-laptop-solver/support/'), false);
    }

    public function test_landing_links_to_company_pages(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('href="'.route('contact').'"', false);
    }
}

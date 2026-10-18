<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Page-cache observers fire on almost every write, so no test may ever
     * touch the real public/page-cache directory.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['blog.page_cache_path' => $this->pageCachePath()]);
        File::deleteDirectory($this->pageCachePath());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pageCachePath());

        parent::tearDown();
    }

    protected function pageCachePath(): string
    {
        return storage_path('framework/testing/page-cache-'.getmypid());
    }

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}

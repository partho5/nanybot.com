<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    public function isCloudinaryUrl(string $url): bool
    {
        return str_contains($url, 'res.cloudinary.com');
    }

    /**
     * Build a delivery URL with transformations. f_auto,q_auto serve AVIF/WebP
     * at an automatic quality; non-Cloudinary URLs are returned untouched.
     */
    public function transform(string $url, string $transformation = ''): string
    {
        if (! $this->isCloudinaryUrl($url) || ! str_contains($url, '/image/upload/')) {
            return $url;
        }

        $chain = trim('f_auto,q_auto'.($transformation ? ','.$transformation : ''), ',');

        return str_replace('/image/upload/', "/image/upload/{$chain}/", $url);
    }

    /**
     * Parameters for a browser upload to the signed preset. The secret never
     * leaves the server; the browser only gets a short-lived signature.
     *
     * @return array{cloud_name: string, api_key: string, upload_preset: string, folder: string, timestamp: int, signature: string}|null
     */
    public function signUpload(): ?array
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        $preset = config('services.cloudinary.upload_preset');
        $folder = config('services.cloudinary.folder');

        if (! $cloudName || ! $apiKey || ! $apiSecret || ! $preset) {
            return null;
        }

        $timestamp = time();

        return [
            'cloud_name' => $cloudName,
            'api_key' => $apiKey,
            'upload_preset' => $preset,
            'folder' => $folder,
            'timestamp' => $timestamp,
            'signature' => sha1("folder={$folder}&timestamp={$timestamp}&upload_preset={$preset}{$apiSecret}"),
        ];
    }

    /**
     * Copy a remote image into Cloudinary (signed upload by URL).
     * Returns [secure_url, width, height] or null when not configured / failed.
     *
     * @return array{0: string, 1: int, 2: int}|null
     */
    public function uploadFromUrl(string $remoteUrl, ?string $folder = null): ?array
    {
        $folder ??= config('services.cloudinary.folder');
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            return null;
        }

        $timestamp = time();
        $signature = sha1("folder={$folder}&timestamp={$timestamp}{$apiSecret}");

        try {
            $response = Http::timeout(30)->asForm()->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                'file' => $remoteUrl,
                'folder' => $folder,
                'api_key' => $apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);

            if (! $response->successful()) {
                Log::warning('Cloudinary remote upload failed.', ['url' => $remoteUrl, 'status' => $response->status()]);

                return null;
            }

            return [$response->json('secure_url'), (int) $response->json('width'), (int) $response->json('height')];
        } catch (\Throwable $e) {
            Log::warning('Cloudinary remote upload failed.', ['url' => $remoteUrl, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Extract the Cloudinary public ID from a URL.
     *
     * Handles URLs like:
     *   https://res.cloudinary.com/{cloud}/image/upload/v1234567890/folder/file.jpg
     *   https://res.cloudinary.com/{cloud}/image/upload/w_300,h_200/v1234567890/folder/file.jpg
     */
    public function extractPublicId(string $url): ?string
    {
        // Split on '/image/upload/' and take everything after
        $parts = explode('/image/upload/', $url, 2);
        if (count($parts) < 2) {
            return null;
        }

        $path = $parts[1];

        // Strip optional transformation segment (e.g. "w_300,h_200/")
        $path = preg_replace('#^[^/]*,[^/]*/+#', '', $path);

        // Strip version prefix (e.g. "v1234567890/")
        $path = preg_replace('#^v\d+/#', '', $path);

        // Strip file extension
        $path = preg_replace('#\.[a-z0-9]+$#i', '', $path);

        return $path ?: null;
    }

    /**
     * Delete an image from Cloudinary by URL.
     * Silently skips non-Cloudinary URLs or missing credentials.
     */
    public function delete(string $url): void
    {
        if (! $this->isCloudinaryUrl($url)) {
            return;
        }

        $publicId = $this->extractPublicId($url);
        if (! $publicId) {
            return;
        }

        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            Log::warning('Cloudinary credentials not configured; skipping image deletion.', ['url' => $url]);

            return;
        }

        $timestamp = time();
        $signature = sha1("public_id={$publicId}&timestamp={$timestamp}{$apiSecret}");

        try {
            Http::post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
                'public_id' => $publicId,
                'api_key' => $apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to delete Cloudinary image.', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

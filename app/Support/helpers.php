<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('media_url')) {
    /**
     * Resolve a stored media path into a usable URL.
     *
     * Accepts absolute URLs, root-relative paths (assets shipped in /public)
     * and paths stored on the public disk.
     */
    function media_url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:', '//'])) {
            return $path;
        }

        if (Str::startsWith($path, '/')) {
            return url($path);
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('setting')) {
    /** Read a site setting with an optional fallback. */
    function setting(string $key, mixed $default = null): mixed
    {
        return SiteSetting::get($key, $default);
    }
}

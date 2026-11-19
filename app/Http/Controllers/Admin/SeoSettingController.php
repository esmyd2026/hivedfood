<?php

namespace App\Http\Controllers\Admin;

use App\Models\SeoSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeoSettingController extends AdminResourceController
{
    protected array $searchable = ['page_key', 'meta_title'];

    protected array $uploads = ['og_image'];

    protected array $jsonFields = ['structured_data'];

    protected array $toggles = ['in_sitemap'];

    protected string $mediaFolder = 'seo';

    protected ?string $defaultSort = 'page_key';

    protected function model(): string
    {
        return SeoSetting::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'page_key' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('seo_settings', 'page_key')->ignore($record?->getKey()),
            ],
            'path' => ['nullable', 'string', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:180'],
            'og_description' => ['nullable', 'string', 'max:320'],
            'og_image' => ['nullable', 'file', 'image', 'max:4096'],
            'og_type' => ['nullable', 'string', 'max:40'],
            'twitter_card' => ['nullable', 'string', 'max:40'],
            'robots' => ['nullable', 'string', 'max:60'],
            'structured_data' => ['nullable'],
            'sitemap_priority' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'sitemap_changefreq' => ['nullable', Rule::in(['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])],
            'in_sitemap' => ['nullable', 'boolean'],
        ];
    }
}

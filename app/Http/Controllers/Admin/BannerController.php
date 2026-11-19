<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends AdminResourceController
{
    protected array $searchable = ['title', 'subtitle'];

    protected array $uploads = ['image_desktop', 'image_mobile'];

    protected string $mediaFolder = 'banners';

    protected function model(): string
    {
        return Banner::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:600'],
            'cta_text' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'cta_secondary_text' => ['nullable', 'string', 'max:60'],
            'cta_secondary_url' => ['nullable', 'string', 'max:255'],
            'image_desktop' => ['nullable', 'file', 'image', 'max:4096'],
            'image_mobile' => ['nullable', 'file', 'image', 'max:4096'],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'theme' => ['nullable', Rule::in(['dark', 'light'])],
            'align' => ['nullable', Rule::in(['left', 'center'])],
            'transition' => ['nullable', Rule::in(['fade', 'slide', 'zoom'])],
            'duration' => ['nullable', 'integer', 'min:2000', 'max:20000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}

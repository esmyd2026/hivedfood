<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContentSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentSectionController extends AdminResourceController
{
    protected array $with = ['modules'];

    protected array $searchable = ['name', 'title', 'key'];

    protected array $uploads = ['image'];

    protected array $jsonFields = ['buttons', 'settings'];

    protected string $mediaFolder = 'secciones';

    protected function model(): string
    {
        return ContentSection::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'key' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('content_sections', 'key')->ignore($record?->getKey()),
            ],
            'name' => ['required', 'string', 'max:120'],
            'component' => ['required', 'string', 'max:60'],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1500'],
            'image' => ['nullable', 'file', 'image', 'max:4096'],
            'buttons' => ['nullable'],
            'settings' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

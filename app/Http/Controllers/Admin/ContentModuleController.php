<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContentModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentModuleController extends AdminResourceController
{
    public const LAYOUTS = [
        'text_image', 'image_text', 'grid', 'carousel', 'gallery',
        'card', 'stat', 'testimonial', 'cta',
    ];

    protected array $searchable = ['internal_name', 'title'];

    protected array $uploads = ['image'];

    protected array $jsonFields = ['bullets', 'buttons', 'settings'];

    protected string $mediaFolder = 'modulos';

    protected function model(): string
    {
        return ContentModule::class;
    }

    protected function applyFilters(mixed $query, Request $request): void
    {
        if ($request->filled('content_section_id')) {
            $query->where('content_section_id', (int) $request->query('content_section_id'));
        }
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'content_section_id' => ['required', 'integer', 'exists:content_sections,id'],
            'internal_name' => ['required', 'string', 'max:120'],
            'layout' => ['required', Rule::in(self::LAYOUTS)],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1500'],
            'image' => ['nullable', 'file', 'image', 'max:4096'],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'icon' => ['nullable', 'string', 'max:60'],
            'value' => ['nullable', 'string', 'max:60'],
            'bullets' => ['nullable'],
            'buttons' => ['nullable'],
            'settings' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

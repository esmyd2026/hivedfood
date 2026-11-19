<?php

namespace App\Http\Controllers\Admin;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeatureController extends AdminResourceController
{
    protected array $searchable = ['name', 'short_description'];

    protected array $uploads = ['image'];

    protected array $jsonFields = ['bullets'];

    protected array $toggles = ['is_active', 'is_highlighted', 'is_available'];

    protected string $mediaFolder = 'funcionalidades';

    protected function model(): string
    {
        return Feature::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'slug' => [
                'required', 'string', 'max:140', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('features', 'slug')->ignore($record?->getKey()),
            ],
            'category' => ['nullable', 'string', 'max:60'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1500'],
            'icon' => ['nullable', 'string', 'max:60'],
            'image' => ['nullable', 'file', 'image', 'max:4096'],
            'bullets' => ['nullable'],
            'is_highlighted' => ['nullable', 'boolean'],
            'is_available' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

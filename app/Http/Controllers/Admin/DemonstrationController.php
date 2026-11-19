<?php

namespace App\Http\Controllers\Admin;

use App\Models\Demonstration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DemonstrationController extends AdminResourceController
{
    protected array $searchable = ['business_name', 'category'];

    protected array $uploads = ['logo', 'cover_image'];

    protected array $jsonFields = ['highlights', 'screenshots'];

    protected array $toggles = ['is_published', 'is_featured'];

    protected string $mediaFolder = 'demos';

    protected function model(): string
    {
        return Demonstration::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'business_name' => ['required', 'string', 'max:140'],
            'slug' => [
                'required', 'string', 'max:140', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('demonstrations', 'slug')->ignore($record?->getKey()),
            ],
            'category' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'file', 'image', 'max:2048'],
            'cover_image' => ['nullable', 'file', 'image', 'max:4096'],
            'cover_alt' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'demo_url' => ['nullable', 'url', 'max:255'],
            'highlights' => ['nullable'],
            'screenshots' => ['nullable'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'is_published' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

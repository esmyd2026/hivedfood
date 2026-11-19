<?php

namespace App\Http\Controllers\Admin;

use App\Models\BusinessType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessTypeController extends AdminResourceController
{
    protected array $searchable = ['name'];

    protected array $uploads = ['image'];

    protected string $mediaFolder = 'tipos-negocio';

    protected function model(): string
    {
        return BusinessType::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('business_types', 'slug')->ignore($record?->getKey()),
            ],
            'icon' => ['nullable', 'string', 'max:60'],
            'image' => ['nullable', 'file', 'image', 'max:4096'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'allow_free_text' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\PricingItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingItemController extends AdminResourceController
{
    protected array $searchable = ['name', 'short_description'];

    protected array $jsonFields = ['plan_slugs', 'requires', 'requires_any', 'notes', 'benefits'];

    protected array $toggles = ['is_active', 'is_available', 'is_included_by_default'];

    protected function model(): string
    {
        return PricingItem::class;
    }

    protected function applyFilters(mixed $query, Request $request): void
    {
        if ($request->filled('group')) {
            $query->where('group', $request->query('group'));
        }
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'slug' => [
                'required', 'string', 'max:140', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('pricing_items', 'slug')->ignore($record?->getKey()),
            ],
            'group' => ['required', Rule::in(PricingItem::GROUPS)],
            'category' => ['nullable', 'string', 'max:60'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:60'],
            // Precio de implementación y mensualidad por separado: un módulo
            // puede cobrar solo uno de los dos, o ninguno mientras se aprueba.
            'setup_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'monthly_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'currency' => ['nullable', 'string', 'max:8'],
            'unit' => ['nullable', 'string', 'max:40'],
            'unit_kind' => ['nullable', Rule::in(PricingItem::UNIT_KINDS)],
            'allow_quantity' => ['nullable', 'boolean'],
            'min_quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'max_quantity' => ['nullable', 'integer', 'min:1', 'max:1000', 'gte:min_quantity'],
            'included_quantity' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'requires' => ['nullable'],
            'requires_any' => ['nullable'],
            'notes' => ['nullable'],
            'benefits' => ['nullable'],
            'is_included_by_default' => ['nullable', 'boolean'],
            'is_available' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'feature_id' => ['nullable', 'integer', 'exists:features,id'],
            'plan_slugs' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }
}

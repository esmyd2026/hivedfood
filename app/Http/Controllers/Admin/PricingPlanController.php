<?php

namespace App\Http\Controllers\Admin;

use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingPlanController extends AdminResourceController
{
    protected array $searchable = ['name', 'headline'];

    protected array $jsonFields = ['included', 'included_feature_ids', 'included_item_slugs'];

    protected array $toggles = ['is_active', 'is_recommended'];

    protected function model(): string
    {
        return PricingPlan::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'slug' => [
                'required', 'string', 'max:140', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('pricing_plans', 'slug')->ignore($record?->getKey()),
            ],
            // El nivel ordena los paquetes acumulativos: cada uno incluye
            // todo lo del anterior.
            'level' => ['nullable', 'integer', 'min:1', 'max:20'],
            'headline' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:60'],
            'setup_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'monthly_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'currency' => ['nullable', 'string', 'max:8'],
            'included' => ['nullable'],
            'included_feature_ids' => ['nullable'],
            'included_item_slugs' => ['nullable'],
            'is_recommended' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }
}

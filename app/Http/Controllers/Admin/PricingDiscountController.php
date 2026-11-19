<?php

namespace App\Http\Controllers\Admin;

use App\Models\PricingDiscount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingDiscountController extends AdminResourceController
{
    protected array $searchable = ['name', 'code'];

    protected array $jsonFields = ['plan_slugs'];

    protected array $toggles = ['is_active', 'is_automatic'];

    protected function model(): string
    {
        return PricingDiscount::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'code' => [
                'nullable', 'string', 'max:40',
                Rule::unique('pricing_discounts', 'code')->ignore($record?->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'applies_to' => ['required', Rule::in(['setup', 'recurring', 'total'])],
            'min_items' => ['nullable', 'integer', 'min:0', 'max:100'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'plan_slugs' => ['nullable'],
            'is_automatic' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }
}

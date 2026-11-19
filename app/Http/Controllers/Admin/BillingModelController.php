<?php

namespace App\Http\Controllers\Admin;

use App\Models\BillingModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingModelController extends AdminResourceController
{
    protected array $with = ['tiers'];

    protected array $searchable = ['name', 'headline'];

    protected array $jsonFields = ['included', 'conditions'];

    protected array $toggles = ['is_active', 'is_recommended', 'charges_monthly', 'charges_usage'];

    protected function model(): string
    {
        return BillingModel::class;
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'slug' => [
                'required', 'string', 'max:140', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('billing_models', 'slug')->ignore($record?->getKey()),
            ],
            'type' => ['required', Rule::in(BillingModel::TYPES)],
            'headline' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:60'],
            'currency' => ['nullable', 'string', 'max:8'],
            // Qué cobra la modalidad. Los precios de paquetes, módulos y
            // locales no se tocan desde aquí: viven en el catálogo.
            'charges_monthly' => ['nullable', 'boolean'],
            'charges_usage' => ['nullable', 'boolean'],
            'included_orders' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'min_monthly' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'volume_basis' => ['required', Rule::in(['branch', 'total'])],
            'included' => ['nullable'],
            'conditions' => ['nullable'],
            'is_recommended' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }
}

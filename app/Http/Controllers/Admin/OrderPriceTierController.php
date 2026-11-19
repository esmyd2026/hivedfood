<?php

namespace App\Http\Controllers\Admin;

use App\Models\OrderPriceTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class OrderPriceTierController extends AdminResourceController
{
    protected array $searchable = ['label'];

    protected ?string $defaultSort = 'from_orders';

    protected function model(): string
    {
        return OrderPriceTier::class;
    }

    protected function applyFilters(mixed $query, Request $request): void
    {
        if ($request->filled('billing_model_id')) {
            $query->where('billing_model_id', (int) $request->query('billing_model_id'));
        }
    }

    protected function rules(Request $request, ?Model $record): array
    {
        return [
            'billing_model_id' => ['required', 'integer', 'exists:billing_models,id'],
            'label' => ['nullable', 'string', 'max:120'],
            'from_orders' => ['required', 'integer', 'min:1', 'max:1000000'],
            'to_orders' => ['nullable', 'integer', 'min:1', 'max:1000000', 'gte:from_orders'],
            'price_per_order' => ['required', 'numeric', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

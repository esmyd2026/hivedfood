<?php

namespace App\Models;

use App\Concerns\RecordsPricingChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PricingDiscount extends Model
{
    use RecordsPricingChanges;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_items' => 'integer',
            'min_subtotal' => 'decimal:2',
            'plan_slugs' => 'array',
            'is_automatic' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(fn (Builder $q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today))
            ->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today));
    }
}

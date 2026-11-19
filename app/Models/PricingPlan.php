<?php

namespace App\Models;

use App\Concerns\RecordsPricingChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPlan extends Model
{
    use RecordsPricingChanges;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'setup_price' => 'decimal:2',
            'monthly_price' => 'decimal:2',
            'level' => 'integer',
            'included' => 'array',
            'included_item_slugs' => 'array',
            'included_feature_ids' => 'array',
            'is_recommended' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'pricing_plan_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Only plans whose validity window covers today. */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(fn (Builder $q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today))
            ->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today));
    }

    public function hasPricing(): bool
    {
        return $this->setup_price !== null || $this->monthly_price !== null;
    }

    /** ¿El paquete ya trae este módulo incluido? */
    public function includes(string $slug): bool
    {
        return in_array($slug, $this->included_item_slugs ?? [], true);
    }
}

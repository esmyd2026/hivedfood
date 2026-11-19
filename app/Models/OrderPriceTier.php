<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPriceTier extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'from_orders' => 'integer',
            'to_orders' => 'integer',
            'price_per_order' => 'decimal:4',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function billingModel(): BelongsTo
    {
        return $this->belongsTo(BillingModel::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('from_orders')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Does this tier cover the given monthly volume? */
    public function covers(int $orders): bool
    {
        if ($orders < $this->from_orders) {
            return false;
        }

        return $this->to_orders === null || $orders <= $this->to_orders;
    }

    /** Human label such as "501 a 1.000 pedidos". */
    public function range(): string
    {
        if ($this->to_orders === null) {
            return 'Más de '.number_format($this->from_orders - 1, 0, ',', '.').' pedidos';
        }

        if ($this->from_orders <= 1) {
            return 'Hasta '.number_format($this->to_orders, 0, ',', '.').' pedidos';
        }

        return number_format($this->from_orders, 0, ',', '.').' a '.number_format($this->to_orders, 0, ',', '.').' pedidos';
    }
}

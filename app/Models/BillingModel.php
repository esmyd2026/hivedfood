<?php

namespace App\Models;

use App\Concerns\RecordsPricingChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modalidad de cobro de una cotización.
 *
 * Decide únicamente dos cosas: si se cobra la mensualidad fija del catálogo y
 * si además se cobra el consumo por pedidos. Los precios de paquetes, módulos
 * y sucursales no viven aquí: así ninguna pantalla puede cobrar dos veces el
 * mismo concepto.
 */
class BillingModel extends Model
{
    use RecordsPricingChanges;

    public const TYPES = ['monthly', 'per_order', 'commission'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'min_monthly' => 'decimal:2',
            'commission_percent' => 'decimal:3',
            'charges_monthly' => 'boolean',
            'charges_usage' => 'boolean',
            'included_orders' => 'integer',
            'included' => 'array',
            'conditions' => 'array',
            'is_recommended' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    /** @return array<int, string> */
    public function auditedAttributes(): array
    {
        return [
            'name', 'type', 'charges_monthly', 'charges_usage', 'included_orders',
            'volume_basis', 'min_monthly', 'commission_percent', 'is_active',
        ];
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(OrderPriceTier::class)->orderBy('from_orders')->orderBy('id');
    }

    public function activeTiers(): HasMany
    {
        return $this->tiers()->where('is_active', true);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
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

    /** El volumen se cuenta por sucursal salvo que la modalidad diga lo contrario. */
    public function countsVolumePerBranch(): bool
    {
        return $this->volume_basis !== 'total';
    }

    /** ¿Puede ya poner una cifra por sí sola? */
    public function hasPricing(): bool
    {
        if ($this->charges_usage) {
            return $this->activeTiers()->exists();
        }

        if ($this->type === 'commission') {
            return $this->commission_percent !== null;
        }

        // Una modalidad que solo cobra el catálogo no necesita tarifa propia.
        return true;
    }
}

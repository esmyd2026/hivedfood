<?php

namespace App\Models;

use App\Concerns\RecordsPricingChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catálogo comercial: módulos adicionales, escalabilidad y servicios.
 *
 * Cada elemento lleva su precio de implementación y su mensualidad. Los que
 * no se venden por separado (porque solo llegan dentro de un paquete) siguen
 * aquí para poder resolver las dependencias entre módulos.
 */
class PricingItem extends Model
{
    use RecordsPricingChanges;

    /** module = adicional contratable · scalability = sucursales, marcas, números · service = implementación */
    public const GROUPS = ['module', 'scalability', 'service'];

    /** Unidades de escalabilidad reconocidas por el calculador. */
    public const UNIT_KINDS = ['branch', 'brand', 'whatsapp', 'training'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'setup_price' => 'decimal:2',
            'monthly_price' => 'decimal:2',
            'allow_quantity' => 'boolean',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'included_quantity' => 'integer',
            'is_included_by_default' => 'boolean',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'plan_slugs' => 'array',
            'requires' => 'array',
            'requires_any' => 'array',
            'notes' => 'array',
            'benefits' => 'array',
            'sort_order' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
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

    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /** Escalabilidad de un tipo concreto: sucursales, marcas, números… */
    public function scopeUnit(Builder $query, string $kind): Builder
    {
        return $query->where('group', 'scalability')->where('unit_kind', $kind);
    }

    /** True when this item may be combined with the given plan. */
    public function availableForPlan(?string $planSlug): bool
    {
        if (empty($this->plan_slugs)) {
            return true;
        }

        return $planSlug !== null && in_array($planSlug, $this->plan_slugs, true);
    }

    /** Un módulo sin ningún precio cargado todavía. */
    public function hasPricing(): bool
    {
        return $this->setup_price !== null || $this->monthly_price !== null;
    }
}

<?php

namespace App\Concerns;

use App\Models\PricingAudit;
use Illuminate\Database\Eloquent\Model;

/**
 * Deja constancia de cada cambio en el tarifario.
 *
 * Solo se guardan los atributos que afectan al precio o a la disponibilidad
 * comercial: el historial tiene que leerse, no inundarse de ruido.
 */
trait RecordsPricingChanges
{
    /** @return array<int, string> */
    public function auditedAttributes(): array
    {
        return [
            'name', 'setup_price', 'monthly_price', 'currency', 'is_active',
            'included_quantity', 'requires', 'requires_any', 'included_item_slugs',
            'level', 'value', 'type', 'applies_to', 'is_available',
        ];
    }

    public static function bootRecordsPricingChanges(): void
    {
        static::created(fn (Model $model) => $model->recordPricingAudit('created', $model->auditableSnapshot()));

        static::updated(function (Model $model) {
            $changes = collect($model->getChanges())
                ->only($model->auditedAttributes())
                ->map(fn ($value, $key) => [
                    'from' => $model->getOriginal($key),
                    'to' => $value,
                ])
                ->all();

            if ($changes !== []) {
                $model->recordPricingAudit('updated', $changes);
            }
        });

        static::deleted(fn (Model $model) => $model->recordPricingAudit('deleted', $model->auditableSnapshot()));
    }

    /** @return array<string, mixed> */
    public function auditableSnapshot(): array
    {
        return collect($this->getAttributes())->only($this->auditedAttributes())->all();
    }

    /** @param array<string, mixed> $changes */
    public function recordPricingAudit(string $action, array $changes): void
    {
        PricingAudit::query()->create([
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'label' => $this->name ?? $this->slug ?? ('#'.$this->getKey()),
            'action' => $action,
            'changes' => $changes,
            'user_id' => auth()->id(),
        ]);
    }
}

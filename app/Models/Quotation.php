<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    public const STATUSES = ['draft', 'sent', 'negotiating', 'accepted', 'rejected', 'expired'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'setup_subtotal' => 'decimal:2',
            'recurring_subtotal' => 'decimal:2',
            'setup_discount' => 'decimal:2',
            'recurring_discount' => 'decimal:2',
            'setup_total' => 'decimal:2',
            'recurring_total' => 'decimal:2',
            'total' => 'decimal:2',
            'is_priced' => 'boolean',
            'discounts' => 'array',
            'conditions' => 'array',
            'payload' => 'array',
            'pricing_snapshot' => 'array',
            'valid_until' => 'date',
            'emailed_at' => 'datetime',
            'implementation_requested_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    public function billingModel(): BelongsTo
    {
        return $this->belongsTo(BillingModel::class);
    }

    public function duplicatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicated_from_id');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }
}

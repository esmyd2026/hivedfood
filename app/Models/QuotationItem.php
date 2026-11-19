<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'setup_price' => 'decimal:2',
            'monthly_price' => 'decimal:2',
            'quantity' => 'integer',
            'is_priced' => 'boolean',
            'is_included' => 'boolean',
            'is_auto_added' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function pricingItem(): BelongsTo
    {
        return $this->belongsTo(PricingItem::class);
    }
}

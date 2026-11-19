<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    public const STATUSES = ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'];

    public const SOURCES = ['quotation', 'demo_request', 'contact_form', 'whatsapp', 'newsletter'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'accepts_marketing' => 'boolean',
            'contacted_at' => 'datetime',
        ];
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['new', 'contacted']);
    }
}

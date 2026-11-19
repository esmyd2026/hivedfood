<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
    protected $table = 'media';

    protected $guarded = ['id'];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'is_protected' => 'boolean',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    public function getUrlAttribute(): ?string
    {
        return media_url($this->path);
    }

    /** Files shipped inside /public are read-only from the panel. */
    public function isShipped(): bool
    {
        return $this->source === 'shipped' || Str::startsWith($this->path, '/');
    }

    public function deleteFile(): void
    {
        if ($this->isShipped()) {
            return;
        }

        Storage::disk('public')->delete($this->path);
    }
}

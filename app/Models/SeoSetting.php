<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'structured_data' => 'array',
            'in_sitemap' => 'boolean',
            'sitemap_priority' => 'float',
        ];
    }
}

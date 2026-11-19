<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    public const CACHE_KEY = 'hivedfood.site_settings';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** All settings as a key => decoded value map. */
    public static function all_values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->get()->mapWithKeys(fn (self $s) => [
                $s->key => $s->decoded(),
            ])->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all_values()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, string $group = 'general', string $type = 'string'): self
    {
        $setting = static::query()->firstOrNew(['key' => $key]);
        $setting->group = $setting->group ?: $group;
        $setting->type = $setting->exists ? $setting->type : $type;
        $setting->value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        $setting->save();

        return $setting;
    }

    public function decoded(): mixed
    {
        return match ($this->type) {
            'json' => json_decode((string) $this->value, true) ?: [],
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            default => $this->value,
        };
    }
}

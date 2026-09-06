<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = ['group', 'key', 'value', 'type', 'label', 'is_public', 'sort_order'];

    public const CACHE_KEY = 'kj.settings';

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** All settings as a key => cast-value map, cached for a day. */
    public static function all2(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), function () {
            return static::query()->get()->mapWithKeys(fn (self $s) => [$s->key => $s->castValue()])->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all2()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, string $group = 'general', string $type = 'string'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'group' => $group, 'type' => $type],
        );

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    public function castValue(): mixed
    {
        return match ($this->type) {
            'int' => (int) $this->value,
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }
}

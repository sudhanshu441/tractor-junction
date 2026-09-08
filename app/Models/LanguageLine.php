<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LanguageLine extends Model
{
    use HasFactory;

    protected $table = 'language_lines';

    protected $fillable = ['locale', 'group', 'key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn (self $line) => self::flush($line->locale));
        static::deleted(fn (self $line) => self::flush($line->locale));
    }

    /**
     * Overrides for one locale, as a plain key => value array.
     *
     * Plain arrays only — a cached Eloquent collection comes back as an
     * incomplete class once the store serialises.
     *
     * @return array<string, string>
     */
    public static function overrides(string $locale): array
    {
        return Cache::remember("kj.lang.{$locale}", now()->addHours(6), function () use ($locale) {
            return static::where('locale', $locale)
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->pluck('value', 'key')
                ->all();
        });
    }

    public static function flush(?string $locale = null): void
    {
        foreach ($locale ? [$locale] : array_keys(config('kj.locales', [])) as $code) {
            Cache::forget("kj.lang.{$code}");
        }
    }
}

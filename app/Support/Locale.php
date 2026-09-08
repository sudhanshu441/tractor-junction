<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

/**
 * URL handling for the bilingual site.
 *
 * English is served at the bare path and Hindi under /hi, so an English URL
 * never changes shape when Hindi is added — existing links and rankings survive.
 */
class Locale
{
    public const DEFAULT = 'en';

    /** @return array<string, string> supported code → label */
    public static function supported(): array
    {
        return config('kj.locales', ['en' => 'English']);
    }

    public static function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($code, self::supported());
    }

    /** The path with any locale prefix stripped, always starting with "/". */
    public static function stripPrefix(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        $segments = explode('/', trim($path, '/'));

        if (self::isSupported($segments[0] ?? null) && $segments[0] !== self::DEFAULT) {
            array_shift($segments);
        }

        return '/'.implode('/', $segments);
    }

    /** The current URL rewritten for another locale, query string intact. */
    public static function urlFor(string $code, ?string $path = null): string
    {
        $path = self::stripPrefix($path ?? Request::path());
        $query = $path === (Request::path() === '/' ? '/' : '/'.Request::path())
            ? Request::getQueryString()
            : null;

        $url = $code === self::DEFAULT
            ? url($path)
            : url('/'.$code.rtrim($path, '/'));

        return $query ? $url.'?'.$query : $url;
    }

    /**
     * hreflang targets for the current page.
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $alternates = [];

        foreach (array_keys(self::supported()) as $code) {
            $alternates[$code] = self::urlFor($code);
        }

        return $alternates;
    }
}

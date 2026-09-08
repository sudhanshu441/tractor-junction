<?php

namespace App\Support;

use Illuminate\Routing\UrlGenerator;

/**
 * Makes route() locale-aware without touching a single view.
 *
 * The public routes are registered twice — bare for English, and again under
 * /hi with an "hi." name prefix. Rewriting the name here at generation time
 * (rather than at registration time) means route caching still works and every
 * existing route('products.show', …) call keeps pointing at the page the reader
 * is actually on.
 *
 * Admin, dealer, account, AJAX and signed document routes have no localised
 * twin, so they fall straight through.
 */
class LocalizedUrlGenerator extends UrlGenerator
{
    public function route($name, $parameters = [], $absolute = true)
    {
        return parent::route($this->localised($name), $parameters, $absolute);
    }

    /** @param mixed $name */
    private function localised($name): mixed
    {
        if (! is_string($name)) {
            return $name;
        }

        $locale = app()->getLocale();

        if ($locale === Locale::DEFAULT || str_starts_with($name, $locale.'.')) {
            return $name;
        }

        $localised = $locale.'.'.$name;

        return $this->routes->hasNamedRoute($localised) ? $localised : $name;
    }
}

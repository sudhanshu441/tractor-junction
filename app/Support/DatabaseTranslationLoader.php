<?php

namespace App\Support;

use App\Models\LanguageLine;
use Illuminate\Translation\FileLoader;

/**
 * Lets operations correct a Hindi label without a deploy.
 *
 * The JSON and PHP language files stay the source of truth — a `language_lines`
 * row is layered on top. That ordering matters: a developer adding a new string
 * ships it in the file and it works immediately; an editor fixing wording
 * overrides it in the database and it survives the next deploy.
 *
 * Database failures are swallowed: a page must still render in English if the
 * translations table is unreachable.
 */
class DatabaseTranslationLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null): array
    {
        $lines = parent::load($locale, $group, $namespace);

        if ($namespace !== null && $namespace !== '*') {
            return $lines;
        }

        try {
            return array_merge($lines, LanguageLine::overrides($locale));
        } catch (\Throwable) {
            return $lines;
        }
    }
}

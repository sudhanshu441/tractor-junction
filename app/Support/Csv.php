<?php

namespace App\Support;

/**
 * CSV writing with the escape character pinned.
 *
 * PHP 8.4 deprecates calling fputcsv() without $escape and PHP 9 changes the
 * default, so every call site has to state what it wants. An empty escape is
 * what spreadsheets actually expect: RFC 4180 doubles a quote inside a quoted
 * field and has no backslash escape at all, and PHP's historical default
 * produces values Excel then reads back wrong.
 */
class Csv
{
    /** @param resource $stream */
    public static function put($stream, array $fields): void
    {
        fputcsv($stream, $fields, ',', '"', '', "\n");
    }
}

<?php

namespace App\Domain\Reporting\Services;

use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * One report, described once.
 *
 * A report is a title, a query and a set of columns. CSV, Excel and PDF are
 * three renderings of that same definition, so a column added here appears in
 * all three and cannot drift between them.
 *
 * The query is never run here — the exporters chunk it, because a year of
 * leads must not be assembled in memory before the first byte is sent.
 */
class ReportDefinition
{
    /**
     * @param  string  $key  url segment, e.g. "leads"
     * @param  string  $title  shown on the PDF and in the filename
     * @param  \Closure():Builder  $query
     * @param  array<string, \Closure(mixed):(string|int|float|null)>  $columns  heading => value
     * @param  array<int, string>  $numeric  headings that should right-align
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly \Closure $query,
        public readonly array $columns,
        public readonly array $numeric = [],
        public readonly ?string $subtitle = null,
        public readonly string $orientation = 'portrait',
    ) {}

    /** @return array<int, string> */
    public function headings(): array
    {
        return array_keys($this->columns);
    }

    /** @return array<int, string|int|float|null> */
    public function row(mixed $model): array
    {
        return array_map(fn (\Closure $get) => $get($model), array_values($this->columns));
    }

    public function isNumeric(string $heading): bool
    {
        return in_array($heading, $this->numeric, true);
    }

    public function filename(string $extension): string
    {
        return config('kj.brand.slug').'-'.$this->key.'-'.now()->format('Y-m-d').'.'.$extension;
    }
}

<?php

namespace App\Domain\Catalog\Services;

use App\Models\Media;
use App\Models\Product;
use App\Models\SpecAttribute;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * All product writes go through here, so the web controller, an import and a
 * future API endpoint cannot drift apart in how they save a model.
 */
class ProductService
{
    public function __construct(
        private readonly PriceService $prices,
        private readonly MediaService $media,
    ) {}

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                ...$this->attributes($data),
                'slug' => $this->uniqueSlug($data['name'], (int) $data['brand_id']),
            ]);

            $this->syncSpecs($product, $data['specs'] ?? []);
            $this->syncRelations($product, $data);

            return $product->refresh();
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            // The slug is the public URL; it stays put once the product exists.
            $product->update($this->attributes($data));

            $this->syncSpecs($product, $data['specs'] ?? []);
            $this->syncRelations($product, $data);

            return $product->refresh();
        });
    }

    /**
     * Writes spec values into the right typed column for each attribute, and
     * removes any the admin cleared.
     */
    public function syncSpecs(Product $product, array $specs): void
    {
        if (! $specs) {
            return;
        }

        $attributes = SpecAttribute::whereIn('id', array_keys($specs))->get()->keyBy('id');

        foreach ($specs as $attributeId => $raw) {
            $attribute = $attributes->get((int) $attributeId);

            if (! $attribute) {
                continue;
            }

            if ($raw === null || $raw === '') {
                $product->specValues()->where('spec_attribute_id', $attribute->id)->delete();

                continue;
            }

            $product->specValues()->updateOrCreate(
                ['spec_attribute_id' => $attribute->id, 'product_variant_id' => null],
                $this->typedValue($attribute->data_type, $raw),
            );
        }

        $this->syncHorsepower($product);
    }

    /**
     * The "Engine HP" specification is the single source of truth for horsepower.
     *
     * products.hp_min/hp_max are a denormalised range used for sorting, the
     * "45-50 HP" label and the band pages. Deriving them here stops the Basics
     * tab and the Specifications tab from silently disagreeing.
     */
    private function syncHorsepower(Product $product): void
    {
        $hp = $product->specValues()
            ->whereHas('attribute', fn ($q) => $q->where('slug', 'engine-hp'))
            ->value('value_number');

        if ($hp === null) {
            return;
        }

        $product->forceFill([
            'hp_min' => $hp,
            // A max below the spec would be nonsense; leave a genuine range alone.
            'hp_max' => $product->hp_max && (float) $product->hp_max > (float) $hp ? $product->hp_max : null,
        ])->saveQuietly();

        $product->refresh();
    }

    /** @return array{value_string:?string, value_number:?float, value_boolean:?bool, value_json:?array} */
    private function typedValue(string $dataType, mixed $raw): array
    {
        $row = ['value_string' => null, 'value_number' => null, 'value_boolean' => null, 'value_json' => null];

        return match ($dataType) {
            'int', 'decimal' => [...$row, 'value_number' => is_numeric($raw) ? (float) $raw : null],
            'boolean' => [...$row, 'value_boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN)],
            'json' => [...$row, 'value_json' => is_array($raw) ? $raw : json_decode((string) $raw, true)],
            default => [...$row, 'value_string' => (string) $raw],
        };
    }

    private function syncRelations(Product $product, array $data): void
    {
        if (isset($data['features'])) {
            $product->features()->delete();

            foreach (array_values(array_filter($data['features'], fn ($f) => filled($f['title'] ?? null))) as $i => $feature) {
                $product->features()->create([
                    'title' => $feature['title'],
                    'description' => $feature['description'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        if (isset($data['faqs'])) {
            $product->faqs()->delete();

            foreach (array_values(array_filter($data['faqs'], fn ($f) => filled($f['question'] ?? null))) as $i => $faq) {
                $product->faqs()->create([
                    'question' => $faq['question'],
                    'answer' => $faq['answer'] ?? '',
                    'sort_order' => $i,
                    'is_active' => true,
                ]);
            }
        }

        if (isset($data['competitors'])) {
            $product->competitors()->delete();

            foreach (array_values(array_filter($data['competitors'])) as $i => $competitorId) {
                if ((int) $competitorId === $product->id) {
                    continue; // a product is not its own competitor
                }

                $product->competitors()->create([
                    'competitor_product_id' => (int) $competitorId,
                    'sort_order' => $i,
                ]);
            }
        }

        if (isset($data['videos'])) {
            $product->videos()->delete();

            foreach (array_values(array_filter($data['videos'], fn ($v) => filled($v['youtube_id'] ?? null))) as $i => $video) {
                $product->videos()->create([
                    'title' => $video['title'] ?? '',
                    'youtube_id' => $this->youtubeId($video['youtube_id']),
                    'type' => $video['type'] ?? 'review',
                    'sort_order' => $i,
                ]);
            }
        }
    }

    /** @param  UploadedFile[]  $files */
    public function attachImages(Product $product, array $files): int
    {
        $added = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $this->media->store($product, $file, 'gallery', $product->full_name);
            $added++;
        }

        if ($added && ! $product->media()->where('collection', 'primary')->exists()) {
            $product->media()->first()?->update(['collection' => 'primary']);
        }

        return $added;
    }

    public function makePrimaryImage(Product $product, Media $media): void
    {
        $product->media()->where('collection', 'primary')->update(['collection' => 'gallery']);
        $media->update(['collection' => 'primary']);
    }

    private function attributes(array $data): array
    {
        return collect($data)->only([
            'brand_id', 'category_id', 'name', 'model_code', 'status', 'hp_min', 'hp_max',
            'launch_year', 'expected_launch_date', 'short_description', 'description',
            'is_featured', 'is_popular', 'is_active',
        ])->all();
    }

    /** Slugs only need to be unique within a brand — the URL carries the brand. */
    private function uniqueSlug(string $name, int $brandId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Product::withTrashed()->where('brand_id', $brandId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** Accepts a bare id or any common YouTube URL shape. */
    private function youtubeId(string $input): string
    {
        if (preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{6,20})~', $input, $m)) {
            return $m[1];
        }

        return Str::limit(trim($input), 20, '');
    }
}

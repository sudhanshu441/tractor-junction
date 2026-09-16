<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Services\ProductService;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Finder\Finder;

/**
 * Loads real product photographs in bulk.
 *
 * Clicking 58 models through the admin panel is how photo libraries never get
 * uploaded. Point this at a folder from the manufacturer's dealer pack and it
 * matches each file to a model by filename.
 *
 * Accepted shapes, most specific first:
 *   mahindra-575-di-xp-plus.jpg        brand slug + model slug
 *   mahindra/575-di-xp-plus.jpg        brand folder + model slug
 *   575-di-xp-plus-2.jpg               model slug, numbered for a gallery
 */
class ImportCatalogImages extends Command
{
    protected $signature = 'catalog:import-images
                            {folder : Folder holding the photographs}
                            {--dry-run : Report what would be attached and change nothing}
                            {--replace : Remove a model\'s existing photographs first}';

    protected $description = 'Attach product photographs in bulk by matching filenames to models';

    public function handle(ProductService $products): int
    {
        $folder = rtrim($this->argument('folder'), '/');

        if (! is_dir($folder)) {
            $this->error("No such folder: {$folder}");

            return self::FAILURE;
        }

        $files = iterator_to_array(
            (new Finder)->files()->in($folder)->name('/\.(jpe?g|png|webp)$/i')->sortByName(),
            false,
        );

        if ($files === []) {
            $this->warn('No jpg, png or webp files found there.');

            return self::SUCCESS;
        }

        $this->line(sprintf('Found %d image file(s).', count($files)));

        $attached = 0;
        $unmatched = [];
        $replaced = [];

        foreach ($files as $file) {
            $product = $this->match($file->getRelativePathname(), $file->getFilenameWithoutExtension());

            if (! $product) {
                $unmatched[] = $file->getRelativePathname();

                continue;
            }

            $this->line(sprintf('  %-46s -> %s', $file->getRelativePathname(), $product->full_name));

            if ($this->option('dry-run')) {
                $attached++;

                continue;
            }

            if ($this->option('replace') && ! isset($replaced[$product->id])) {
                $product->media()->delete();
                $replaced[$product->id] = true;
            }

            // Copied, not moved: re-running after a mistake must still be possible.
            $products->attachImages($product, [new UploadedFile(
                $file->getPathname(),
                $file->getFilename(),
                null,
                null,
                true,   // already on disk, skip the upload validity check
            )]);

            $attached++;
        }

        $this->newLine();
        $this->info(($this->option('dry-run') ? 'Would attach ' : 'Attached ').$attached.' image(s).');

        if ($unmatched !== []) {
            $this->warn(count($unmatched).' file(s) matched no model:');

            foreach (array_slice($unmatched, 0, 15) as $name) {
                $this->line('  '.$name);
            }

            $this->line('Name a file after the model slug, optionally prefixed with the brand.');
        }

        return self::SUCCESS;
    }

    /** Most specific match first, so a brand prefix beats a bare model slug. */
    private function match(string $relativePath, string $basename): ?Product
    {
        // Strip a trailing gallery number: 575-di-xp-plus-2 -> 575-di-xp-plus
        $slug = preg_replace('/-\d+$/', '', strtolower($basename));

        $folder = str_contains($relativePath, '/')
            ? strtolower(explode('/', $relativePath)[0])
            : null;

        // brand folder + model slug
        if ($folder && $brand = Brand::where('slug', $folder)->first()) {
            if ($product = Product::where('brand_id', $brand->id)->where('slug', $slug)->first()) {
                return $product;
            }
        }

        // brand slug baked into the filename
        foreach (Brand::pluck('id', 'slug') as $brandSlug => $brandId) {
            if (str_starts_with($slug, $brandSlug.'-')) {
                $modelSlug = substr($slug, strlen($brandSlug) + 1);

                if ($product = Product::where('brand_id', $brandId)->where('slug', $modelSlug)->first()) {
                    return $product;
                }
            }
        }

        // bare model slug, only when it is unambiguous across brands
        $matches = Product::where('slug', $slug)->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}

<?php

namespace App\Domain\Catalog\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Stores an upload and derives the three sizes the front end asks for.
 * Conversions are recorded on the media row so views never guess a path.
 */
class MediaService
{
    public const CONVERSIONS = ['thumb' => 300, 'card' => 600, 'detail' => 1200];

    public function store(Model $model, UploadedFile $file, string $collection = 'gallery', ?string $altText = null): Media
    {
        $folder = $this->folderFor($model);
        $name = Str::uuid()->toString();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        $path = $file->storeAs($folder, "{$name}.{$extension}", 'public');

        return Media::create([
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'collection' => $collection,
            'file_name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'conversions' => $this->makeConversions($path, $folder, $name),
            'alt_text' => $altText,
            'sort_order' => (int) Media::where('model_type', $model->getMorphClass())
                ->where('model_id', $model->getKey())->max('sort_order') + 1,
        ]);
    }

    /**
     * @return array<string, string> conversion name => stored path
     */
    private function makeConversions(string $originalPath, string $folder, string $name): array
    {
        $conversions = [];

        try {
            $manager = new ImageManager(new Driver);
            $source = Storage::disk('public')->path($originalPath);

            foreach (self::CONVERSIONS as $label => $width) {
                $target = "{$folder}/{$name}-{$label}.webp";

                $image = $manager->read($source)->scaleDown(width: $width);
                Storage::disk('public')->put($target, (string) $image->toWebp(82));

                $conversions[$label] = $target;
            }
        } catch (\Throwable $e) {
            // A conversion failure must not lose the upload; the original still serves.
            Log::warning('Image conversion failed: '.$e->getMessage(), ['path' => $originalPath]);
        }

        return $conversions;
    }

    public function delete(Media $media): void
    {
        foreach (array_merge([$media->path], array_values($media->conversions ?? [])) as $path) {
            Storage::disk($media->disk)->delete($path);
        }

        $media->delete();
    }

    private function folderFor(Model $model): string
    {
        return Str::plural(Str::snake(class_basename($model))).'/'.$model->getKey();
    }
}

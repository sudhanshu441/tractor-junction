<?php

namespace Database\Seeders;

use App\Domain\Marketplace\Services\ListingService;
use App\Models\District;
use App\Models\Product;
use App\Models\UsedListing;
use App\Models\UsedListingImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * A demo used marketplace so moderation, leads and the seller panel are
 * explorable before real sellers arrive. Sample data — delete before launch.
 */
class DemoListingSeeder extends Seeder
{
    private ?\GdImage $basePhoto = null;

    public function run(): void
    {
        $listings = app(ListingService::class);

        $sellers = collect([
            ['Ramesh Kumar', '9812300001'],
            ['Vijay Singh', '9812300002'],
            ['Anil Kumar', '9812300003'],
            ['Sunil Yadav', '9812300004'],
        ])->map(function (array $row) {
            $user = User::firstOrCreate(
                ['mobile' => $row[1]],
                [
                    'name' => $row[0],
                    // An email as well, so the seller panel can be opened with the
                    // password login while exploring the demo data.
                    'email' => str($row[0])->slug().'@example.com',
                    'mobile_verified_at' => now(),
                    'password' => Hash::make('KrishiDemo@2026'),
                    'user_type' => 'customer',
                    'is_active' => true,
                ],
            );

            if (! $user->hasRole('customer')) {
                $user->assignRole('customer');
            }

            return $user;
        });

        $districts = District::whereIn('slug', ['sitapur', 'hardoi', 'lakhimpur-kheri', 'barabanki', 'ludhiana', 'nashik'])
            ->with('state', 'cities')->get();

        if ($districts->isEmpty() || $sellers->isEmpty()) {
            $this->command?->warn('Skipping demo listings — geography or sellers missing.');

            return;
        }

        $models = Product::with('brand', 'category')
            ->whereHas('category', fn ($q) => $q->where('type', 'tractor'))
            ->inRandomOrder()->limit(18)->get();

        $created = 0;

        foreach ($models as $i => $model) {
            $district = $districts[$i % $districts->count()];
            $seller = $sellers[$i % $sellers->count()];
            $year = 2015 + ($i % 9);
            $hours = 600 + ($i * 347) % 5200;
            $condition = ['excellent', 'good', 'good', 'average'][$i % 4];

            // Ask roughly what the machine is worth, with a little seller optimism.
            $askingPrice = round(((float) $model->price_min) * (0.62 - ($i % 5) * 0.05), -3);

            $existing = UsedListing::where('user_id', $seller->id)
                ->where('product_id', $model->id)->first();

            if ($existing) {
                continue;
            }

            $listing = $listings->saveDraft([
                'category_id' => $model->category_id,
                'brand_id' => $model->brand_id,
                'product_id' => $model->id,
                'manufacturing_year' => $year,
                'engine_hours' => $hours,
                'hp' => $model->hp_min,
                'condition' => $condition,
                'tyre_condition_front' => ['new', 'good', 'worn'][$i % 3],
                'tyre_condition_rear' => ['good', 'worn', 'good'][$i % 3],
                'has_rc' => true,
                'has_insurance' => $i % 3 !== 0,
                'expected_price' => max(80000, $askingPrice),
                'is_price_negotiable' => true,
                'description' => "Well maintained {$model->brand->name} {$model->name}, single owner, all papers in order. Sample listing for demonstration.",
                'state_id' => $district->state_id,
                'district_id' => $district->id,
                'city_id' => $district->cities->first()?->id,
                'seller_type' => 'owner',
            ], null, $seller->id);

            $this->attachPlaceholderPhotos($listing, $model->brand->name.' '.$model->name);

            $listings->submit($listing);

            // Leave a few in the moderation queue so it is not empty on first look.
            if ($i % 6 !== 0) {
                $listings->approve($listing, 'Seeded demo listing');

                if ($i % 4 === 0) {
                    $listing->forceFill(['is_verified' => true])->save();
                }
            }

            $created++;
        }

        $this->command?->info("Demo marketplace: {$created} used listings from {$sellers->count()} sellers");
        $this->command?->warn('Sample listings — remove before launch. Seller password: KrishiDemo@2026');
    }

    /**
     * Gives every demo listing the site-wide placeholder photograph, stamped
     * with the listing and the angle so the moderation queue — a photo-review
     * screen — stays reviewable. Real listings carry seller photography.
     */
    private function attachPlaceholderPhotos(UsedListing $listing, string $label): void
    {
        $angles = ['front', 'rear', 'engine', 'meter'];
        $folder = 'used/'.now()->format('Y/m').'/'.$listing->reference_no;

        foreach ($angles as $i => $angle) {
            $path = $folder.'/'.$angle.'.jpg';

            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, $this->placeholderPhoto($label, $angle));
            }

            UsedListingImage::updateOrCreate(
                ['used_listing_id' => $listing->id, 'angle' => $angle],
                [
                    'path' => $path,
                    'thumbnail_path' => $path,
                    'is_primary' => $i === 0,
                    'sort_order' => $i,
                ],
            );
        }
    }

    /**
     * The stamp is not decoration: without it a reviewer sees four identical
     * tractors and cannot tell demo data from a seller's upload, and the
     * photograph is of one machine while the listing may be another.
     */
    private function placeholderPhoto(string $label, string $angle): string
    {
        // Decoded once: a demo run stamps four photographs per listing and the
        // JPEG decode, not the drawing, is what a seeder would spend its time on.
        $this->basePhoto ??= imagecreatefromjpeg(public_path('assets/brand/machines/tractor.jpg'));

        $width = imagesx($this->basePhoto);
        $height = imagesy($this->basePhoto);

        // Drawing mutates, so each stamp gets its own copy of the photograph.
        $image = imagecreatetruecolor($width, $height);
        imagecopy($image, $this->basePhoto, 0, 0, 0, 0, $width, $height);

        $bar = imagecolorallocatealpha($image, 10, 24, 16, 38);
        $white = imagecolorallocate($image, 255, 255, 255);

        // Inset from the bottom edge: the detail hero crops a 4:3 photograph to
        // 68%, and a stamp flush with the edge loses its second line exactly
        // where it matters most — beside the asking price.
        imagefilledrectangle($image, 0, $height - 108, $width, $height - 46, $bar);
        imagestring($image, 5, 18, $height - 100, substr($label, 0, 46), $white);
        imagestring($image, 3, 18, $height - 74, 'SAMPLE PHOTO - '.strtoupper($angle).' - NOT THIS MACHINE', $white);

        ob_start();
        imagejpeg($image, null, 82);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}

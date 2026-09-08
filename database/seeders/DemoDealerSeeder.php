<?php

namespace Database\Seeders;

use App\Domain\Dealer\Services\DealerService;
use App\Domain\Engagement\Services\ReviewService;
use App\Models\Brand;
use App\Models\City;
use App\Models\Dealer;
use App\Models\District;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A demo dealer network so routing, the dealer panel and the directory are
 * explorable before real dealers sign up. Sample data — delete before launch.
 *
 * One dealer is deliberately left pending so the verification queue is never
 * empty on a fresh install.
 */
class DemoDealerSeeder extends Seeder
{
    public function run(): void
    {
        $dealers = app(DealerService::class);
        $reviews = app(ReviewService::class);

        $districts = District::whereIn('slug', ['sitapur', 'hardoi', 'ludhiana', 'nashik'])
            ->with('state', 'cities')->get()->keyBy('slug');

        if ($districts->isEmpty()) {
            $this->command?->warn('Skipping demo dealers — geography missing.');

            return;
        }

        $rows = [
            ['Shri Balaji Tractors', 'sitapur', 'authorised', ['Mahindra', 'Swaraj'], '9811100001', 'verified'],
            ['Kisan Agro Motors', 'hardoi', 'multi_brand', ['Sonalika', 'Farmtrac', 'Eicher'], '9811100002', 'verified'],
            ['Punjab Tractor House', 'ludhiana', 'authorised', ['John Deere'], '9811100003', 'verified'],
            ['Godavari Farm Equipment', 'nashik', 'implement', ['Mahindra'], '9811100004', 'pending'],
        ];

        foreach ($rows as [$name, $districtSlug, $type, $brandNames, $mobile, $status]) {
            $district = $districts->get($districtSlug);

            if (! $district) {
                continue;
            }

            if (Dealer::where('mobile', $mobile)->exists()) {
                continue;
            }

            $slug = str($name)->slug()->toString();

            $owner = User::firstOrCreate(
                ['mobile' => $mobile],
                [
                    'name' => str($name)->before(' ')->append(' ')->append(__('Dealer'))->toString(),
                    // An email as well as a mobile, so the panel can be opened
                    // with the password login while exploring the demo data.
                    'email' => $slug.'@example.com',
                    'mobile_verified_at' => now(),
                    'password' => Hash::make('KrishiDemo@2026'),
                    'user_type' => 'customer',
                    'is_active' => true,
                ],
            );

            $city = $district->cities->first() ?? City::where('district_id', $district->id)->first();
            $brandIds = Brand::whereIn('name', $brandNames)->pluck('id')->all();

            $dealer = $dealers->register([
                'business_name' => $name,
                'display_name' => $name,
                'dealer_type' => $type,
                'contact_person' => $owner->name,
                'mobile' => $mobile,
                'email' => $slug.'@example.com',
                'address' => __('Main Road, near the mandi'),
                'state_id' => $district->state_id,
                'district_id' => $district->id,
                'city_id' => $city?->id,
                'about' => __('Sales, service and genuine spares for :brands, serving :district and the districts around it.', [
                    'brands' => implode(', ', $brandNames),
                    'district' => $district->name,
                ]),
                'working_hours' => __('Mon–Sat, 9am to 7pm'),
            ], $brandIds, $owner);

            $dealer->branches()->create([
                'name' => __(':name — head office', ['name' => $name]),
                'address' => __('Main Road, near the mandi'),
                'state_id' => $district->state_id,
                'district_id' => $district->id,
                'city_id' => $city?->id,
                'mobile' => $mobile,
                'is_head_office' => true,
                'is_active' => true,
            ]);

            $stock = Product::whereHas('brand', fn ($q) => $q->whereIn('name', $brandNames))
                ->whereHas('category', fn ($q) => $q->where('type', 'tractor'))
                ->limit(4)->get();

            foreach ($stock as $product) {
                $dealer->inventory()->updateOrCreate(
                    ['product_id' => $product->id, 'product_variant_id' => null],
                    ['quantity' => random_int(1, 4), 'availability' => 'in_stock', 'is_active' => true],
                );
            }

            if ($status === 'verified') {
                $dealers->changeStatus($dealer, 'verified', __('Documents checked at onboarding.'));
                $this->addReviews($reviews, $dealer);
            }
        }

        $this->seedProductReviews($reviews);

        $this->command?->info('  Demo dealers: '.Dealer::count().' dealers · '
            .Dealer::where('verification_status', 'verified')->count().' verified');
    }

    private function addReviews(ReviewService $reviews, Dealer $dealer): void
    {
        $authors = User::where('user_type', 'customer')->limit(2)->get();

        foreach ($authors as $i => $author) {
            $review = $reviews->submit($dealer, $author, [
                'rating' => 5 - $i,
                'title' => $i === 0 ? __('Straight answers on price') : __('Service takes a day too long'),
                'body' => $i === 0
                    ? __('Quoted the same on-road price they had told me on the phone, and the finance paperwork was done the same afternoon. No hidden accessory charges.')
                    : __('Machine is fine and the sale was smooth, but the workshop kept my tractor an extra day at the first service. Staff are polite about it.'),
                'pros' => $i === 0 ? __('Honest pricing, quick finance') : __('Genuine spares in stock'),
                'cons' => $i === 0 ? null : __('Workshop runs behind in season'),
            ], ['service' => 5 - $i, 'value' => 5 - $i]);

            $review->forceFill(['status' => 'approved'])->save();
        }

        $reviews->recalculateAggregate($dealer->refresh());
    }

    private function seedProductReviews(ReviewService $reviews): void
    {
        $authors = User::where('user_type', 'customer')->limit(3)->get();
        $products = Product::whereHas('category', fn ($q) => $q->where('type', 'tractor'))
            ->limit(4)->get();

        if ($authors->isEmpty() || $products->isEmpty()) {
            return;
        }

        $bodies = [
            [5, __('Pulls a nine-tyne cultivator through black soil without struggling. Diesel works out around four litres an hour on field work.'), __('Strong lift, easy steering'), __('Seat could be softer')],
            [4, __('Two seasons in and nothing has gone wrong beyond routine service. Spares are available in my block, which matters more than the brochure.'), __('Reliable, cheap spares'), __('Brakes need frequent adjustment')],
            [3, __('Good on the road and for haulage, but underpowered for a rotavator in wet soil. Buy the higher HP if you do puddling.'), __('Comfortable for road work'), __('Struggles with heavy implements')],
        ];

        foreach ($products as $index => $product) {
            foreach ($bodies as $i => [$rating, $body, $pros, $cons]) {
                $author = $authors[($index + $i) % $authors->count()];

                if ($product->reviews()->where('user_id', $author->id)->exists()) {
                    continue;
                }

                $review = $reviews->submit($product, $author, [
                    'rating' => $rating,
                    'body' => $body,
                    'pros' => $pros,
                    'cons' => $cons,
                    'ownership_duration' => ['1_to_3_years', 'over_3_years', 'under_6_months'][$i],
                ], [
                    'mileage' => $rating,
                    'comfort' => max(1, $rating - 1),
                    'maintenance' => $rating,
                    'performance' => $rating,
                    'value' => $rating,
                ]);

                // The last one stays pending so the moderation queue has work in it.
                if (! ($index === 0 && $i === count($bodies) - 1)) {
                    $review->forceFill(['status' => 'approved'])->save();
                }
            }

            $reviews->recalculateAggregate($product->refresh());
        }
    }
}

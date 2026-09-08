<?php

namespace Tests\Feature\Engagement;

use App\Domain\Engagement\Services\ReviewService;
use App\Domain\Lead\Services\LeadService;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private ReviewService $reviews;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, CatalogMasterSeeder::class,
            DemoProductSeeder::class, MarketplaceMasterSeeder::class,
        ]);

        $this->reviews = app(ReviewService::class);
        $this->product = Product::with('brand')->firstOrFail();
    }

    private function payload(int $rating = 4): array
    {
        return [
            'rating' => $rating,
            'title' => 'Solid on black soil',
            'body' => 'Two seasons in and it has pulled everything I have hitched to it without complaint.',
        ];
    }

    public function test_a_review_is_never_published_by_its_author(): void
    {
        $review = $this->reviews->submit($this->product, User::factory()->create(), $this->payload());

        $this->assertSame('pending', $review->status);
        $this->assertSame(0, $this->product->refresh()->rating_count);
    }

    public function test_only_approved_reviews_count_towards_the_cached_average(): void
    {
        $this->reviews->submit($this->product, User::factory()->create(), $this->payload(5))
            ->forceFill(['status' => 'approved'])->save();
        $this->reviews->submit($this->product, User::factory()->create(), $this->payload(1));

        $this->reviews->recalculateAggregate($this->product);

        $this->assertSame(1, $this->product->refresh()->rating_count);
        $this->assertSame('5.00', $this->product->rating_avg);
    }

    public function test_aspect_scores_outside_the_subject_are_dropped(): void
    {
        $review = $this->reviews->submit($this->product, User::factory()->create(), $this->payload(), [
            'mileage' => 4,
            'service' => 5,      // a dealer aspect — not valid on a model
        ]);

        $this->assertSame(['mileage'], $review->aspectRatings->pluck('aspect')->all());
    }

    public function test_enquiring_through_us_earns_the_verified_owner_badge(): void
    {
        $buyer = User::factory()->create(['user_type' => 'customer']);

        app(LeadService::class)->capture('new_product', [
            'name' => $buyer->name, 'mobile' => $buyer->mobile,
        ], $this->product, $buyer);

        $review = $this->reviews->submit($this->product, $buyer, $this->payload());
        $stranger = $this->reviews->submit($this->product, User::factory()->create(), $this->payload());

        $this->assertTrue($review->is_verified_owner);
        $this->assertFalse($stranger->is_verified_owner);
    }

    public function test_a_helpful_vote_counts_once_per_person_however_often_they_click(): void
    {
        $review = $this->reviews->submit($this->product, User::factory()->create(), $this->payload());
        $voter = User::factory()->create();

        $this->reviews->vote($review, $voter, true);
        $counts = $this->reviews->vote($review, $voter, true);

        $this->assertSame(1, $counts['helpful']);
    }

    public function test_changing_your_mind_moves_the_vote_rather_than_adding_one(): void
    {
        $review = $this->reviews->submit($this->product, User::factory()->create(), $this->payload());
        $voter = User::factory()->create();

        $this->reviews->vote($review, $voter, true);
        $counts = $this->reviews->vote($review, $voter, false);

        $this->assertSame(0, $counts['helpful']);
        $this->assertSame(1, $counts['unhelpful']);
    }

    public function test_the_summary_reports_the_distribution_and_aspect_averages(): void
    {
        foreach ([5, 5, 3] as $rating) {
            $this->reviews->submit($this->product, User::factory()->create(), $this->payload($rating), ['mileage' => $rating])
                ->forceFill(['status' => 'approved'])->save();
        }

        $summary = $this->reviews->summary($this->product);

        $this->assertSame(3, $summary['total']);
        $this->assertSame(4.3, $summary['average']);
        $this->assertSame(2, $summary['distribution'][5]);
        $this->assertSame(4.3, $summary['aspects']['mileage']);
    }

    public function test_a_guest_cannot_post_a_review(): void
    {
        $this->postJson(route('ajax.reviews.store'), [
            'subject_type' => 'product', 'subject_id' => $this->product->id, ...$this->payload(),
        ])->assertUnauthorized();
    }

    public function test_the_same_person_cannot_review_the_same_model_twice(): void
    {
        $author = User::factory()->create(['user_type' => 'customer']);

        $this->actingAs($author)->postJson(route('ajax.reviews.store'), [
            'subject_type' => 'product', 'subject_id' => $this->product->id, ...$this->payload(),
        ])->assertOk();

        $this->actingAs($author)->postJson(route('ajax.reviews.store'), [
            'subject_type' => 'product', 'subject_id' => $this->product->id, ...$this->payload(),
        ])->assertStatus(422);

        $this->assertSame(1, Review::count());
    }

    public function test_a_pending_review_is_not_shown_on_the_public_page(): void
    {
        $this->reviews->submit($this->product, User::factory()->create(), [
            'rating' => 1, 'body' => 'This machine is a lemon and the dealer is worse than the machine.',
        ]);

        $this->get(route('products.show', [$this->product->brand->slug, $this->product->slug]))
            ->assertOk()
            ->assertDontSee('lemon');
    }
}

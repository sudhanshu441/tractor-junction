<?php

namespace Tests\Feature\Marketplace;

use App\Models\District;
use App\Models\OtpVerification;
use App\Models\Product;
use App\Models\UsedListing;
use App\Models\User;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, NotificationTemplateSeeder::class,
            CatalogMasterSeeder::class, DemoProductSeeder::class, MarketplaceMasterSeeder::class,
        ]);
    }

    /** Walks steps 1-5 and returns the draft. */
    private function walkToPhotos(): UsedListing
    {
        $product = Product::with('brand')->firstOrFail();
        $district = District::firstOrFail();

        $this->postJson('/ajax/sell/step/1', ['category_id' => $product->category_id])
            ->assertOk()->assertJsonPath('status', 'ok');

        $this->postJson('/ajax/sell/step/2', [
            'brand_id' => $product->brand_id,
            'product_id' => $product->id,
            'manufacturing_year' => 2019,
        ])->assertOk();

        $this->postJson('/ajax/sell/step/3', [
            'engine_hours' => 2100, 'condition' => 'good', 'has_rc' => 1,
        ])->assertOk();

        $this->postJson('/ajax/sell/step/5', [
            'expected_price' => 385000,
            'state_id' => $district->state_id,
            'district_id' => $district->id,
        ])->assertOk();

        return UsedListing::latest('id')->firstOrFail();
    }

    public function test_the_wizard_page_renders_for_a_guest(): void
    {
        $this->get('/sell')->assertOk()->assertSee('Sell your machinery');
    }

    public function test_each_step_saves_a_draft_server_side(): void
    {
        $draft = $this->walkToPhotos();

        $this->assertSame('draft', $draft->status);
        $this->assertSame(385000.0, (float) $draft->expected_price);
        $this->assertSame('good', $draft->condition);
    }

    public function test_a_step_rejects_invalid_input(): void
    {
        $this->postJson('/ajax/sell/step/2', ['manufacturing_year' => 1900])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['brand_id', 'manufacturing_year']);
    }

    public function test_step_three_returns_a_fair_price_estimate(): void
    {
        $this->walkToPhotos();

        $response = $this->postJson('/ajax/sell/step/3', ['condition' => 'good', 'engine_hours' => 2100])
            ->assertOk();

        $valuation = $response->json('data.valuation');

        $this->assertNotNull($valuation);
        $this->assertLessThan($valuation['max'], $valuation['min']);
    }

    public function test_photos_upload_one_at_a_time_and_are_capped(): void
    {
        $this->walkToPhotos();

        $this->postJson('/ajax/sell/photo', [
            'photo' => UploadedFile::fake()->image('front.jpg', 800, 600),
            'angle' => 'front',
        ])->assertOk()->assertJsonPath('data.count', 1);

        $max = (int) config('kj.listings.max_photos');

        for ($i = 1; $i < $max; $i++) {
            $this->postJson('/ajax/sell/photo', ['photo' => UploadedFile::fake()->image("p{$i}.jpg")])->assertOk();
        }

        $this->postJson('/ajax/sell/photo', ['photo' => UploadedFile::fake()->image('extra.jpg')])
            ->assertStatus(422);
    }

    public function test_the_first_photo_becomes_the_primary(): void
    {
        $draft = $this->walkToPhotos();

        $this->postJson('/ajax/sell/photo', ['photo' => UploadedFile::fake()->image('a.jpg')])->assertOk();
        $this->postJson('/ajax/sell/photo', ['photo' => UploadedFile::fake()->image('b.jpg')])->assertOk();

        $this->assertSame(1, $draft->images()->where('is_primary', true)->count());
    }

    public function test_it_refuses_to_submit_without_enough_photos(): void
    {
        $this->walkToPhotos();

        $this->postJson('/ajax/sell/photo', ['photo' => UploadedFile::fake()->image('a.jpg')])->assertOk();

        $this->postJson('/ajax/sell/submit', [
            'name' => 'Ramesh', 'mobile' => '9876500001', 'otp' => '123456',
        ])->assertStatus(422);
    }

    public function test_submitting_verifies_the_seller_and_queues_the_listing(): void
    {
        $draft = $this->walkToPhotos();

        for ($i = 0; $i < config('kj.listings.min_photos'); $i++) {
            $this->postJson('/ajax/sell/photo', ['photo' => UploadedFile::fake()->image("p{$i}.jpg")])->assertOk();
        }

        OtpVerification::create([
            'mobile' => '9876500001',
            'otp_hash' => Hash::make('123456'),
            'purpose' => 'listing',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/ajax/sell/submit', [
            'name' => 'Ramesh Kumar', 'mobile' => '9876500001', 'otp' => '123456',
        ])->assertOk()->assertJsonPath('status', 'ok');

        $draft->refresh();

        $this->assertSame('pending', $draft->status);
        $this->assertNotNull($draft->title);

        // A guest who sells gets an account, verified, so they can track it.
        $seller = User::where('mobile', '9876500001')->firstOrFail();
        $this->assertSame($seller->id, $draft->user_id);
        $this->assertNotNull($seller->mobile_verified_at);
        $this->assertTrue($seller->hasRole('customer'));
    }

    public function test_a_wrong_code_does_not_submit_the_listing(): void
    {
        $draft = $this->walkToPhotos();

        for ($i = 0; $i < config('kj.listings.min_photos'); $i++) {
            $this->postJson('/ajax/sell/photo', ['photo' => UploadedFile::fake()->image("p{$i}.jpg")])->assertOk();
        }

        OtpVerification::create([
            'mobile' => '9876500001', 'otp_hash' => Hash::make('123456'),
            'purpose' => 'listing', 'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/ajax/sell/submit', [
            'name' => 'Ramesh', 'mobile' => '9876500001', 'otp' => '999999',
        ])->assertStatus(422);

        $this->assertSame('draft', $draft->fresh()->status);
    }
}

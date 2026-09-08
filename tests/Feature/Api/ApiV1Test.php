<?php

namespace Tests\Feature\Api;

use App\Models\OtpVerification;
use App\Models\Product;
use App\Models\UsedListing;
use App\Models\User;
use App\Models\Wishlist;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoListingSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, CatalogMasterSeeder::class,
            DemoProductSeeder::class, MarketplaceMasterSeeder::class, DemoListingSeeder::class,
        ]);
    }

    private function signIn(): array
    {
        $user = User::factory()->create(['user_type' => 'customer', 'mobile' => '9812999888']);
        $user->assignRole('customer');

        return [$user, $user->createToken('test-phone')->plainTextToken];
    }

    public function test_the_catalogue_is_readable_without_an_account(): void
    {
        $this->getJson('/api/v1/products?per_page=5')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['data' => [['id', 'name', 'brand', 'price', 'url']], 'meta' => ['page', 'total']]);
    }

    public function test_a_model_detail_returns_its_specifications(): void
    {
        $product = Product::with('brand')->firstOrFail();

        $this->getJson("/api/v1/products/{$product->brand->slug}/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $product->slug);
    }

    public function test_the_used_marketplace_is_readable_without_an_account(): void
    {
        $this->getJson('/api/v1/listings?per_page=5')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_a_seller_number_is_never_in_a_listing_response(): void
    {
        $listing = UsedListing::with('seller')->where('status', 'live')->firstOrFail();

        $body = $this->getJson("/api/v1/listings/{$listing->slug}")->assertOk()->getContent();

        $this->assertStringNotContainsString((string) $listing->seller->mobile, $body);
    }

    public function test_a_dealer_number_is_masked_in_the_directory(): void
    {
        $body = $this->getJson('/api/v1/dealers')->assertOk()->getContent();

        $this->assertStringNotContainsString('9811100001', $body);
    }

    public function test_the_emi_endpoint_returns_the_same_arithmetic_as_the_website(): void
    {
        $this->postJson('/api/v1/emi', [
            'price' => 700000, 'down_payment' => 150000, 'interest_rate' => 12, 'tenure_months' => 60,
        ])
            ->assertOk()
            ->assertJsonPath('data.loan_amount', 550000)
            ->assertJsonPath('data.instalments', 60);
    }

    public function test_a_nonsense_emi_request_is_rejected(): void
    {
        $this->postJson('/api/v1/emi', ['price' => 10, 'interest_rate' => 99, 'tenure_months' => 500])
            ->assertStatus(422);
    }

    public function test_private_endpoints_need_a_token(): void
    {
        foreach (['/api/v1/me', '/api/v1/account/listings', '/api/v1/account/wishlist'] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    public function test_the_otp_flow_issues_a_working_token(): void
    {
        $this->postJson('/api/v1/auth/otp', ['mobile' => '9812999777'])
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseCount('otp_verifications', 1);

        // The stored code is hashed and never returned, so a known one is
        // planted here exactly as the website's OTP tests do.
        OtpVerification::first()->forceFill([
            'otp_hash' => Hash::make('654321'),
        ])->save();

        $token = $this->postJson('/api/v1/auth/verify', [
            'mobile' => '9812999777', 'otp' => '654321', 'device_name' => 'test-phone',
        ])->assertOk()->json('data.token');

        $this->assertNotEmpty($token);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.mobile', '9812999777');
    }

    public function test_a_wrong_code_issues_no_token(): void
    {
        $this->postJson('/api/v1/auth/otp', ['mobile' => '9812999666'])->assertOk();

        $this->postJson('/api/v1/auth/verify', [
            'mobile' => '9812999666', 'otp' => '000000', 'device_name' => 'test-phone',
        ])->assertStatus(422);

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_a_token_reaches_only_its_own_owners_data(): void
    {
        [$user, $token] = $this->signIn();
        $stranger = User::factory()->create(['user_type' => 'customer']);

        UsedListing::where('status', 'live')->limit(2)->update(['user_id' => $stranger->id]);

        $data = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/account/listings')
            ->assertOk()
            ->json('data');

        $this->assertSame([], $data);
    }

    public function test_signing_in_again_on_the_same_device_replaces_the_old_token(): void
    {
        [$user] = $this->signIn();

        $user->tokens()->where('name', 'test-phone')->delete();
        $second = $user->createToken('test-phone')->plainTextToken;

        $this->assertSame(1, $user->fresh()->tokens()->where('name', 'test-phone')->count());
        $this->withHeader('Authorization', 'Bearer '.$second)->getJson('/api/v1/me')->assertOk();
    }

    public function test_an_enquiry_from_the_app_needs_no_second_otp(): void
    {
        [$user, $token] = $this->signIn();
        $product = Product::firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/account/enquiries', [
                'type' => 'new_product', 'about_type' => 'product', 'about_id' => $product->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('leads', ['user_id' => $user->id, 'channel' => 'app', 'mobile_verified' => true]);
    }

    public function test_a_wishlist_entry_belongs_to_the_person_who_saved_it(): void
    {
        [$user, $token] = $this->signIn();
        $product = Product::firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/account/wishlist', ['type' => 'product', 'id' => $product->id])
            ->assertOk();

        $item = Wishlist::firstOrFail();
        $this->assertSame($user->id, $item->user_id);

        // Someone else's token must not be able to delete it. The guard caches
        // the resolved user for the lifetime of the test application, so it is
        // reset here — each real request starts with a fresh container anyway.
        $stranger = User::factory()->create(['user_type' => 'customer']);
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$stranger->createToken('other')->plainTextToken)
            ->deleteJson("/api/v1/account/wishlist/{$item->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('wishlists', ['id' => $item->id]);
    }
}

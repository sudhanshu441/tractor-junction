<?php

namespace Tests\Feature\Analytics;

use App\Domain\Analytics\Services\VisitorJourney;
use App\Http\Middleware\IdentifyVisitor;
use App\Models\Lead;
use App\Models\PageView;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The line this feature must not cross: it records what a browser looked at.
 * A name and a number exist only because somebody typed them into a form.
 */
class VisitorJourneyTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, CatalogMasterSeeder::class,
            DemoProductSeeder::class, MarketplaceMasterSeeder::class,
        ]);
    }

    private function browse(string $url, ?string $visitor = null)
    {
        $request = $this->withHeader('User-Agent', self::BROWSER);

        if ($visitor) {
            $request = $request->withUnencryptedCookie(IdentifyVisitor::COOKIE, $visitor);
        }

        return $request->get($url);
    }

    public function test_a_visitor_is_given_a_cookie_and_their_pages_are_recorded(): void
    {
        $this->browse('/tractors')->assertOk();

        $this->assertSame(1, PageView::whereNotNull('visitor_id')->count());
        $this->assertSame('buy', PageView::first()->intent);
    }

    public function test_a_crawler_is_given_no_cookie_and_leaves_no_trail(): void
    {
        $this->withHeader('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)')
            ->get('/tractors')->assertOk();

        $this->assertSame(0, PageView::whereNotNull('visitor_id')->count());
    }

    public function test_the_intent_of_a_page_is_recognised_including_in_hindi(): void
    {
        $journey = app(VisitorJourney::class);

        $this->assertSame('buy', $journey->intentFor('/tractors/mahindra/575'));
        $this->assertSame('sell', $journey->intentFor('/sell'));
        $this->assertSame('finance', $journey->intentFor('/loan/emi-calculator'));
        $this->assertSame('insurance', $journey->intentFor('/tractor-insurance'));

        // A Hindi URL carries the same intent as its English twin.
        $this->assertSame('buy', $journey->intentFor('/hi/tractors'));
        $this->assertSame('sell', $journey->intentFor('/hi/sell'));

        $this->assertNull($journey->intentFor('/'));
    }

    public function test_a_journey_survives_the_session_and_summarises_what_was_viewed(): void
    {
        $visitor = (string) Str::uuid();
        $product = Product::with('brand')->firstOrFail();

        $this->browse('/tractors', $visitor);
        $this->browse("/tractors/{$product->brand->slug}/{$product->slug}", $visitor);

        $summary = app(VisitorJourney::class)->summarise($visitor);

        $this->assertSame('buy', $summary['primary_intent']);
        $this->assertSame(2, $summary['pages_viewed']);
        $this->assertContains($product->full_name, $summary['machines_viewed']);
        $this->assertContains($product->brand->name, $summary['brands_viewed']);
    }

    public function test_a_callback_request_creates_a_lead_carrying_the_journey(): void
    {
        $visitor = (string) Str::uuid();
        $product = Product::with('brand')->firstOrFail();

        $this->browse("/tractors/{$product->brand->slug}/{$product->slug}", $visitor);

        // withCredentials(): Laravel's test client sends no cookies at all on a
        // JSON request without it. A real browser sends them on same-origin
        // XHR, so this restores production behaviour rather than working around
        // anything in the application.
        $this->withHeader('User-Agent', self::BROWSER)
            ->withUnencryptedCookie(IdentifyVisitor::COOKIE, $visitor)
            ->withCredentials()
            ->postJson(route('ajax.callback.store'), [
                'name' => 'Ravi Patel', 'mobile' => '9876501234', 'interest' => 'buy', 'consent' => 1,
            ])
            ->assertOk();

        $lead = Lead::where('type', 'callback')->firstOrFail();

        $this->assertSame('Ravi Patel', $lead->name);
        $this->assertSame($visitor, $lead->visitor_id);
        $this->assertFalse((bool) $lead->mobile_verified);
        $this->assertContains($product->full_name, $lead->meta['journey']['machines_viewed']);
        $this->assertNotNull($lead->meta['consent_given_at']);
    }

    public function test_a_callback_without_consent_is_refused(): void
    {
        $this->postJson(route('ajax.callback.store'), [
            'name' => 'Ravi Patel', 'mobile' => '9876501234',
        ])->assertStatus(422)->assertJsonValidationErrors('consent');

        $this->assertSame(0, Lead::count());
    }

    public function test_a_forged_visitor_cookie_is_ignored(): void
    {
        $this->withHeader('User-Agent', self::BROWSER)
            ->withUnencryptedCookie(IdentifyVisitor::COOKIE, "'; DROP TABLE leads; --")
            ->get('/tractors')->assertOk();

        // A fresh uuid was issued instead of trusting what the browser sent.
        $stored = PageView::whereNotNull('visitor_id')->value('visitor_id');

        $this->assertTrue(Str::isUuid($stored));
    }

    public function test_the_prompt_appears_on_a_high_intent_page_and_not_elsewhere(): void
    {
        $this->browse('/sell')->assertOk()->assertSee('kj-callback', false);
        $this->browse('/tractors')->assertOk()->assertSee('kj-callback', false);

        // Editorial pages are for reading, not for interrupting.
        $this->browse('/faq')->assertOk()->assertDontSee('kj-callback', false);
    }

    public function test_a_signed_in_person_is_never_prompted(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);

        $this->actingAs($user)->withHeader('User-Agent', self::BROWSER)
            ->get('/tractors')->assertOk()->assertDontSee('kj-callback', false);
    }

    public function test_the_admin_visitor_screen_shows_journeys_and_which_converted(): void
    {
        $visitor = (string) Str::uuid();
        $this->browse('/tractors', $visitor);

        $admin = User::factory()->create(['user_type' => 'staff']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('admin.visitors.index'))
            ->assertOk()
            ->assertSee('Buy', false)
            ->assertSee('anonymous', false);
    }
}

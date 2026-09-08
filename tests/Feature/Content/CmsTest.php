<?php

namespace Tests\Feature\Content;

use App\Domain\Content\Services\ContentService;
use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\ContactMessage;
use App\Models\Menu;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, ContentSeeder::class,
        ]);
    }

    public function test_the_news_section_renders_with_javascript_off(): void
    {
        $this->get(route('blogs.index'))
            ->assertOk()
            ->assertSee('How much horsepower do you actually need?', false);
    }

    public function test_a_draft_is_not_public(): void
    {
        $post = Blog::first();
        $post->forceFill(['status' => 'draft'])->save();

        $this->get(route('blogs.show', $post->slug))->assertNotFound();
    }

    public function test_a_scheduled_post_appears_by_itself_when_its_time_passes(): void
    {
        $post = Blog::first();
        $post->forceFill(['status' => 'scheduled', 'published_at' => now()->addHour()])->save();

        $this->get(route('blogs.show', $post->slug))->assertNotFound();

        $this->travel(2)->hours();

        $this->get(route('blogs.show', $post->slug))->assertOk();
    }

    public function test_the_publisher_command_catches_up_the_admin_status(): void
    {
        $post = Blog::first();
        $post->forceFill(['status' => 'scheduled', 'published_at' => now()->subMinute()])->save();

        $this->artisan('content:publish-scheduled')->assertSuccessful();

        $this->assertSame('published', $post->fresh()->status);
    }

    public function test_a_comment_is_never_published_by_its_author(): void
    {
        $post = Blog::first();
        $author = User::factory()->create(['user_type' => 'customer']);

        $this->actingAs($author)
            ->postJson(route('ajax.blogs.comment', $post), ['body' => 'This helped me pick a 47 HP model.'])
            ->assertOk();

        $comment = BlogComment::firstOrFail();

        $this->assertSame('pending', $comment->status);
        $this->get(route('blogs.show', $post->slug))->assertDontSee('This helped me pick a 47 HP model.');
    }

    public function test_a_guest_cannot_comment(): void
    {
        $this->postJson(route('ajax.blogs.comment', Blog::first()), ['body' => 'Spam link here for you'])
            ->assertUnauthorized();
    }

    public function test_a_static_page_is_served_from_its_root_slug(): void
    {
        $this->get('/about-us')->assertOk()->assertSee('Krishi Junction is a marketplace', false);
    }

    public function test_the_faq_page_publishes_structured_data(): void
    {
        $html = $this->get(route('faqs.index'))->assertOk()->getContent();

        $this->assertStringContainsString('FAQPage', $html);
        $this->assertStringContainsString('Is Krishi Junction free to use?', $html);
    }

    public function test_a_contact_message_needs_a_way_to_reply(): void
    {
        $this->postJson(route('ajax.contact.store'), [
            'name' => 'Ramesh', 'message' => 'I want to ask about a listing please.',
        ])->assertStatus(422);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_a_contact_message_with_a_mobile_is_stored(): void
    {
        $this->postJson(route('ajax.contact.store'), [
            'name' => 'Ramesh', 'mobile' => '9812345670',
            'message' => 'I want to ask about a listing please.',
        ])->assertOk();

        $this->assertDatabaseHas('contact_messages', ['mobile' => '9812345670', 'status' => 'new']);
    }

    public function test_resubscribing_after_unsubscribing_works_rather_than_erroring(): void
    {
        $this->postJson(route('ajax.newsletter.store'), ['email' => 'farmer@example.com'])->assertOk();

        $this->get(route('newsletter.unsubscribe', ['email' => 'farmer@example.com']))->assertOk();
        $this->assertFalse(NewsletterSubscriber::first()->is_active);

        $this->postJson(route('ajax.newsletter.store'), ['email' => 'farmer@example.com'])->assertOk();

        $this->assertTrue(NewsletterSubscriber::first()->is_active);
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_menus_and_banners_survive_a_warm_cache(): void
    {
        config(['cache.default' => 'database']);
        Cache::flush();

        $service = app(ContentService::class);

        $cold = $service->menu('header');
        $warm = $service->menu('header');

        $this->assertIsArray($warm);
        $this->assertSame($cold, $warm);
        $this->assertNotEmpty($warm);

        // The page that reads them must render on a warm cache too.
        $this->get('/')->assertOk();
        $this->get('/')->assertOk();
    }

    public function test_an_editor_write_clears_the_cached_menu(): void
    {
        $service = app(ContentService::class);

        $before = count($service->menu('header'));

        Menu::where('slug', 'header')->firstOrFail()->items()->create([
            'label' => 'Offers', 'url' => '/offers', 'sort_order' => 99, 'is_active' => true,
        ]);

        $this->assertSame($before + 1, count($service->menu('header')));
    }

    public function test_reading_time_is_never_zero_minutes(): void
    {
        $this->assertSame(1, app(ContentService::class)->readingMinutes('Two words'));
        $this->assertSame(1, app(ContentService::class)->readingMinutes(null));
    }
}

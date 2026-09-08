<?php

namespace App\Http\Controllers\Web;

use App\Domain\Content\Services\ContentService;
use App\Domain\Seo\Services\SeoService;
use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackPageView;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\NewsletterSubscriber;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Product;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly SeoService $seo,
    ) {}

    /** Editor-managed static pages: about, privacy, terms. */
    public function show(string $slug): View
    {
        $page = Page::active()->with('seo')->where('slug', $slug)->firstOrFail();

        TrackPageView::attribute($page);

        return view('web.content.page', [
            'page' => $page,
            'seo' => $this->seo->for($page, 'page', [], [
                ':title' => $page->title,
                ':excerpt' => $this->content->excerptFrom($page->content),
            ]),
        ]);
    }

    public function faqs(Request $request): View
    {
        $categories = Faq::active()->select('category')->distinct()->pluck('category')->all();
        $active = $request->query('category', $categories[0] ?? 'general');

        return view('web.content.faqs', [
            'categories' => $categories,
            'active' => $active,
            'faqs' => $this->content->faqs($active),
            'seo' => $this->seo->for(null, 'page', [
                'title' => __('Frequently Asked Questions'),
                'description' => __('Answers on buying and selling tractors, loans, insurance, dealer enquiries and listings on Krishi Junction.'),
            ]),
        ]);
    }

    public function videos(Request $request): View
    {
        return view('web.content.videos', [
            'videos' => Video::active()->with('product.brand')
                ->when($request->query('product'), fn ($q, $id) => $q->where('product_id', $id))
                ->latest()->paginate(24)->withQueryString(),
            'featured' => Video::active()->where('is_featured', true)->latest()->first(),
            'seo' => $this->seo->for(null, 'page', [
                'title' => __('Tractor Videos — Reviews, Demos & Comparisons'),
                'description' => __('Watch tractor reviews, field demonstrations and model comparisons before you buy.'),
            ]),
        ]);
    }

    public function video(string $slug): View
    {
        $video = Video::active()->with('product.brand')->where('slug', $slug)->firstOrFail();

        $video->incrementQuietly('view_count');

        return view('web.content.video', [
            'video' => $video,
            'more' => Video::active()->where('id', '!=', $video->id)->latest()->limit(6)->get(),
            'seo' => $this->seo->for(null, 'page', [
                'title' => $video->title,
                'description' => $video->description,
            ]),
        ]);
    }

    public function contact(): View
    {
        return view('web.content.contact', [
            'faqs' => $this->content->faqs('general'),
            'seo' => $this->seo->for(null, 'page', [
                'title' => __('Contact Krishi Junction'),
                'description' => __('Reach the Krishi Junction team about a listing, a dealer, a loan application or anything else.'),
            ]),
        ]);
    }

    public function storeContact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['nullable', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if (blank($data['mobile'] ?? null) && blank($data['email'] ?? null)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Leave a mobile number or an email, otherwise we cannot reply.'),
                'errors' => ['mobile' => [__('A mobile number or an email is needed.')]],
            ], 422);
        }

        ContactMessage::create([...$data, 'status' => 'new']);

        return response()->json([
            'status' => 'ok',
            'message' => __('Thank you. Our team will get back to you.'),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);

        // Re-subscribing after unsubscribing should work, not error.
        NewsletterSubscriber::updateOrCreate(
            ['email' => strtolower($data['email'])],
            ['is_active' => true, 'unsubscribed_at' => null],
        );

        return response()->json(['status' => 'ok', 'message' => __('Subscribed. We send one email a week, no more.')]);
    }

    public function unsubscribe(Request $request): View
    {
        $subscriber = NewsletterSubscriber::where('email', strtolower((string) $request->query('email')))->first();

        $subscriber?->forceFill(['is_active' => false, 'unsubscribed_at' => now()])->save();

        return view('web.content.unsubscribed', [
            'found' => (bool) $subscriber,
            'seo' => $this->seo->for(null, 'page', [
                'title' => __('Unsubscribed'),
                'description' => __('You will not receive further emails from Krishi Junction.'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }

    /** Offers are a real landing surface: brand campaigns and seasonal schemes. */
    public function offers(Request $request): View
    {
        $offers = Offer::live()->with('brand')->orderByDesc('id')->paginate(12);

        return view('web.content.offers', [
            'offers' => $offers,
            'seo' => $this->seo->for(null, 'page', [
                'title' => __('Tractor Offers & Discounts This Month'),
                'description' => __('Current cash discounts, exchange bonuses and finance schemes on tractors from every major brand.'),
            ]),
        ]);
    }

    public function offer(string $slug): View
    {
        $offer = Offer::live()->where('slug', $slug)->with(['brand', 'products.brand'])->firstOrFail();

        $offer->incrementQuietly('view_count');

        return view('web.content.offer', [
            'offer' => $offer,
            'seo' => $this->seo->for(null, 'page', [
                'title' => $offer->title,
                'description' => $this->content->excerptFrom($offer->description),
            ]),
        ]);
    }

    /** Model-page FAQ blocks reuse the same store; kept here so one query serves both. */
    public function popularModels(): array
    {
        return Product::where('is_active', true)->where('is_popular', true)
            ->with('brand')->limit(8)->get()->all();
    }
}

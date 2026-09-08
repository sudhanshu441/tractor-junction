<?php

namespace App\Http\Controllers\Web;

use App\Domain\Dealer\Services\DealerService;
use App\Domain\Engagement\Services\ReviewService;
use App\Domain\Seo\Services\SeoService;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Dealer;
use App\Models\District;
use App\Models\Plan;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealerDirectoryController extends Controller
{
    public function __construct(
        private readonly DealerService $dealers,
        private readonly ReviewService $reviews,
    ) {}

    /** /dealers, /dealers/{state}, /dealers/{state}/{district} */
    public function index(Request $request, ?string $stateSlug = null, ?string $districtSlug = null): View
    {
        $state = $stateSlug ? State::active()->where('slug', $stateSlug)->firstOrFail() : null;
        $district = $districtSlug && $state
            ? District::where('state_id', $state->id)->where('slug', $districtSlug)->firstOrFail()
            : null;

        $brand = $request->query('brand')
            ? Brand::active()->where('slug', $request->query('brand'))->first()
            : null;

        $dealers = Dealer::verified()
            ->with(['brands', 'city', 'district', 'state'])
            ->when($state, fn ($q) => $q->where('state_id', $state->id))
            ->when($district, fn ($q) => $q->where('district_id', $district->id))
            ->when($brand, fn ($q) => $q->whereHas('brands', fn ($b) => $b->where('brands.id', $brand->id)))
            ->orderByDesc('is_featured')
            ->orderByDesc('rating_avg')
            ->paginate(18)->withQueryString();

        return view('web.dealers.index', [
            'dealers' => $dealers,
            'state' => $state,
            'district' => $district,
            'brand' => $brand,
            'states' => State::active()->orderBy('name')->get(),
            'districts' => $state ? $state->districts()->active()->orderBy('name')->get() : collect(),
            'brands' => Brand::active()->orderBy('name')->get(),
            'heading' => $this->heading($state, $district, $brand),
        ]);
    }

    public function show(string $slug): View
    {
        $dealer = Dealer::with([
            'brands', 'branches.city', 'city', 'district', 'state',
            'inventory.product.brand', 'usedListings.images',
        ])->where('slug', $slug)->firstOrFail();

        abort_unless($dealer->verification_status === 'verified' && $dealer->is_active, 404);

        return view('web.dealers.show', [
            'dealer' => $dealer,
            'reviews' => $dealer->reviews()->with('user')->approved()->latest()->limit(20)->get(),
            'reviewSummary' => $this->reviews->summary($dealer),
            'seo' => app(SeoService::class)->for($dealer, 'dealer', [], [
                ':name' => $dealer->display_name,
                ':city' => $dealer->city?->name ?? $dealer->district?->name,
            ]),
            'liveListings' => $dealer->usedListings()->live()->with('images')->limit(8)->get(),
        ]);
    }

    /** Public "become a dealer" form. */
    public function joinForm(): View
    {
        return view('web.dealers.join', [
            'states' => State::active()->orderBy('name')->get(),
            'brands' => Brand::active()->orderBy('name')->get(),
            'plans' => Plan::where('audience', 'dealer')->where('is_active', true)
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function join(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:150'],
            'display_name' => ['nullable', 'string', 'max:150'],
            'dealer_type' => ['required', 'in:authorised,multi_brand,used_only,implement'],
            'contact_person' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'state_id' => ['required', 'exists:states,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'pincode' => ['nullable', 'digits:6'],
            'brands' => ['array'],
            'brands.*' => ['integer', 'exists:brands,id'],
            'about' => ['nullable', 'string', 'max:2000'],
        ]);

        if (Dealer::where('mobile', $data['mobile'])->exists()) {
            return back()->withInput()
                ->with('error', __('A dealer is already registered with this mobile number. Please log in instead.'));
        }

        $owner = $request->user() ?? User::where('mobile', $data['mobile'])->first();

        if (! $owner) {
            $owner = User::create([
                'name' => $data['contact_person'],
                'mobile' => $data['mobile'],
                'email' => $data['email'] ?? null,
                'user_type' => 'dealer',
                'is_active' => true,
            ]);
        }

        $dealer = $this->dealers->register(
            [...collect($data)->except('brands')->all(), 'display_name' => $data['display_name'] ?: $data['business_name']],
            $data['brands'] ?? [],
            $owner,
        );

        return redirect()->route('dealers.joined', $dealer->code);
    }

    public function joined(string $code): View
    {
        $dealer = Dealer::where('code', $code)->firstOrFail();

        return view('web.dealers.joined', ['dealer' => $dealer]);
    }

    private function heading(?State $state, ?District $district, ?Brand $brand): string
    {
        $what = $brand ? __(':brand tractor dealers', ['brand' => $brand->name]) : __('Tractor dealers');

        if ($district) {
            return $what.' '.__('in :district, :state', ['district' => $district->name, 'state' => $state->name]);
        }

        if ($state) {
            return $what.' '.__('in :state', ['state' => $state->name]);
        }

        return $what.' '.__('in India');
    }
}

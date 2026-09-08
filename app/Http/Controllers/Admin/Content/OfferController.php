<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function index(): View
    {
        return view('admin.content.offers.index', [
            'offers' => Offer::with('brand')->withCount('products')->latest()->paginate(25),
            'counts' => [
                'live' => Offer::live()->count(),
                'expired' => Offer::where('ends_at', '<', today())->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return $this->form(new Offer(['is_active' => true, 'discount_type' => 'flat']));
    }

    public function edit(Offer $offer): View
    {
        return $this->form($offer->load('products'));
    }

    private function form(Offer $offer): View
    {
        return view('admin.content.offers.form', [
            'offer' => $offer,
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('brand')->where('is_active', true)->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $offer = Offer::create($this->payload($request, new Offer));
        $offer->products()->sync($request->input('product_ids', []));

        activity()->performedOn($offer)->causedBy($request->user())->log('Created offer');

        return redirect()->route('admin.offers.edit', $offer)->with('success', __('Offer created.'));
    }

    public function update(Request $request, Offer $offer): RedirectResponse
    {
        $offer->update($this->payload($request, $offer));
        $offer->products()->sync($request->input('product_ids', []));

        activity()->performedOn($offer)->causedBy($request->user())->log('Updated offer');

        return back()->with('success', __('Offer saved.'));
    }

    public function destroy(Request $request, Offer $offer): RedirectResponse
    {
        $offer->delete();

        activity()->performedOn($offer)->causedBy($request->user())->log('Deleted offer');

        return redirect()->route('admin.offers.index')->with('success', __('Offer deleted.'));
    }

    private function payload(Request $request, Offer $offer): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'discount_type' => ['required', 'in:flat,percent,cashback,exchange,freebie'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'banner_image' => ['nullable', 'image', 'max:4096'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'terms' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($data['title']);
        $base = $slug;
        $i = 1;

        while (Offer::where('slug', $slug)->where('id', '!=', $offer->id)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        $payload = [...$data, 'slug' => $slug, 'is_active' => (bool) ($data['is_active'] ?? false)];

        if ($request->hasFile('banner_image')) {
            $payload['banner_image'] = $request->file('banner_image')->store('offers', 'public');
        } else {
            unset($payload['banner_image']);
        }

        return $payload;
    }
}

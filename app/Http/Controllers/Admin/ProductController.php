<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Services\MediaService;
use App\Domain\Catalog\Services\PriceService;
use App\Domain\Catalog\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly PriceService $prices,
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.products.index', [
            'brands' => Brand::orderBy('name')->pluck('name', 'id'),
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Product::with(['brand', 'category'])->withCount('specValues');

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(fn ($q) => $q
                ->where('products.name', 'like', "%{$search}%")
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%")));
        }

        foreach (['brand_id', 'category_id'] as $filter) {
            if ($value = $request->integer($filter)) {
                $query->where($filter, $value);
            }
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $total = Product::count();
        $filtered = (clone $query)->count();

        $columns = ['name', 'brand_id', 'category_id', 'hp_min', 'price_min', 'view_count', 'is_active'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $rows = $query->orderBy($orderCol, $orderDir)
            ->skip((int) $request->input('start', 0))
            ->take(min(100, (int) $request->input('length', 25)))
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand?->name,
                'category' => $p->category?->name,
                'hp' => $p->hp_label ?? '—',
                'price' => PriceService::range($p->price_min, $p->price_max),
                'specs' => $p->spec_values_count,
                'status' => $p->status,
                'views' => $p->view_count,
                'is_active' => $p->is_active,
                'edit_url' => route('admin.products.edit', $p),
                'view_url' => $p->brand ? route('products.show', [$p->brand->slug, $p->slug]) : null,
                'toggle_url' => route('admin.products.toggle', $p),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ]);
    }

    public function create(): View
    {
        return $this->form(new Product(['is_active' => true, 'status' => 'available']));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        if ($request->hasFile('images')) {
            $this->products->attachImages($product, (array) $request->file('images'));
        }

        activity()->performedOn($product)->causedBy($request->user())->log('Created product');

        return redirect()->route('admin.products.edit', $product)
            ->with('success', __('":name" created. Add prices and images next.', ['name' => $product->name]));
    }

    public function edit(Product $product): View
    {
        return $this->form($product->load([
            'brand', 'category', 'specValues.attribute', 'prices.state',
            'features', 'faqs', 'videos', 'competitors', 'media',
        ]));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());

        if ($request->hasFile('images')) {
            $this->products->attachImages($product, (array) $request->file('images'));
        }

        activity()->performedOn($product)->causedBy($request->user())->log('Updated product');

        return redirect()->route('admin.products.edit', $product)
            ->with('success', __('":name" saved.', ['name' => $product->name]));
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        activity()->performedOn($product)->causedBy($request->user())
            ->log($product->is_active ? 'Published product' : 'Unpublished product');

        return response()->json([
            'status' => 'ok',
            'message' => $product->is_active ? __('Product is live.') : __('Product hidden from the site.'),
            'data' => ['is_active' => $product->is_active],
        ]);
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $product->delete();
        activity()->performedOn($product)->causedBy($request->user())->log('Deleted product');

        return redirect()->route('admin.products.index')->with('success', __('Product deleted.'));
    }

    // ----- media -----

    public function deleteImage(Product $product, Media $media): JsonResponse
    {
        abort_unless($media->model_id === $product->id && $media->model_type === $product->getMorphClass(), 404);

        $this->media->delete($media);

        return response()->json(['status' => 'ok', 'message' => __('Image removed.')]);
    }

    public function primaryImage(Product $product, Media $media): JsonResponse
    {
        abort_unless($media->model_id === $product->id && $media->model_type === $product->getMorphClass(), 404);

        $this->products->makePrimaryImage($product, $media);

        return response()->json(['status' => 'ok', 'message' => __('Primary image updated.')]);
    }

    private function form(Product $product): View
    {
        // The spec form is built from the category mapping, so a new attribute
        // appears here with no code change.
        $category = $product->category ?? Category::where('type', 'tractor')->orderBy('sort_order')->first();

        return view('admin.products.form', [
            'product' => $product,
            'brands' => Brand::active()->orderBy('name')->get(),
            'categories' => Category::active()->orderBy('type')->orderBy('name')->get(),
            'specGroups' => $category
                ? $category->specAttributes()->with('group')->get()->groupBy('group.name')
                : collect(),
            'specValues' => $product->exists
                ? $product->specValues->keyBy('spec_attribute_id')
                : collect(),
            'states' => State::active()->orderBy('name')->get(),
            'competitorOptions' => Product::active()->with('brand')
                ->when($product->exists, fn ($q) => $q->where('id', '!=', $product->id))
                ->orderBy('name')->limit(300)->get(),
            'priceRows' => $product->exists ? $this->prices->stateTable($product) : collect(),
            'nationalPrice' => $product->exists ? $this->prices->resolve($product) : null,
        ]);
    }
}

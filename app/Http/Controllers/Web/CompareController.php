<?php

namespace App\Http\Controllers\Web;

use App\Domain\Catalog\Services\CompareService;
use App\Domain\Catalog\Services\PriceService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function __construct(
        private readonly CompareService $compare,
        private readonly PriceService $prices,
    ) {}

    /** Landing page — whatever is in the visitor's comparison right now. */
    public function index(): View|RedirectResponse
    {
        $products = $this->compare->products();

        if ($products->count() >= 2) {
            return redirect()->route('compare.show', $this->compare->slugFor($products));
        }

        return view('web.compare.index', [
            'products' => $products,
            'popular' => Product::with(['brand', 'media'])->active()->popular()->limit(8)->get(),
        ]);
    }

    /** The indexable page: /compare/mahindra-575-di-xp-plus-vs-swaraj-744-fe */
    public function show(string $slug): View
    {
        $products = $this->compare->productsFromSlug($slug);

        abort_if($products->count() < 2, 404);

        return view('web.compare.show', [
            'products' => $products,
            'matrix' => $this->compare->matrix($products),
            'prices' => $products->mapWithKeys(fn (Product $p) => [$p->id => $this->prices->resolve($p)]),
            'slug' => $slug,
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $result = $this->compare->add((int) $data['product_id']);
        $products = $this->compare->products();

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
            'data' => [
                'count' => $products->count(),
                'compare_url' => $products->count() >= 2
                    ? route('compare.show', $this->compare->slugFor($products))
                    : route('compare.index'),
                'html' => view('partials.ajax.compare-bar', ['products' => $products])->render(),
            ],
        ], $result['status'] === 'ok' ? 200 : 422);
    }

    public function remove(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer']]);

        $this->compare->remove((int) $data['product_id']);
        $products = $this->compare->products();

        return response()->json([
            'status' => 'ok',
            'message' => __('Removed from comparison.'),
            'data' => [
                'count' => $products->count(),
                'html' => view('partials.ajax.compare-bar', ['products' => $products])->render(),
            ],
        ]);
    }

    public function clear(): JsonResponse
    {
        $this->compare->clear();

        return response()->json([
            'status' => 'ok',
            'message' => __('Comparison cleared.'),
            'data' => ['count' => 0, 'html' => view('partials.ajax.compare-bar', ['products' => collect()])->render()],
        ]);
    }
}

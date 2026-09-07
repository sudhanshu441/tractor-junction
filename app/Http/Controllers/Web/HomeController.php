<?php

namespace App\Http\Controllers\Web;

use App\Domain\Catalog\Services\CompareService;
use App\Domain\Catalog\Services\FacetService;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\State;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly FacetService $facets,
        private readonly CompareService $compare,
    ) {}

    public function index(): View
    {
        // Counts are cached; the model sets are not. Each is a small, indexed,
        // limited query, and caching hydrated models across processes is fragile.
        $stats = Cache::remember('home.stats', now()->addMinutes(30), fn () => [
            'models' => Product::active()->count(),
            'brands' => Brand::active()->count(),
            'states' => State::active()->count(),
            'prices' => ProductPrice::count(),
        ]);

        $tractors = fn () => Product::with(['brand', 'media'])->active()->available()
            ->whereHas('category', fn ($q) => $q->where('type', 'tractor'));

        return view('web.home', [
            'stats' => $stats,
            'brands' => Brand::active()->popular()->orderBy('sort_order')->take(12)->get(),
            'popular' => $tractors()->orderByDesc('popularity_score')->take(8)->get(),
            'latest' => $tractors()->orderByDesc('launch_year')->take(4)->get(),
            'implements' => Product::with(['brand', 'media'])->active()
                ->whereHas('category', fn ($q) => $q->where('type', 'implement'))
                ->orderByDesc('popularity_score')->take(4)->get(),
            'hpBands' => $this->facets->hpBands(),
            'priceBands' => $this->facets->priceBands(),
            'states' => State::active()->orderBy('name')->get(),
            'compareIds' => $this->compare->ids(),
        ]);
    }
}

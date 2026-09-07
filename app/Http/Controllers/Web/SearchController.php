<?php

namespace App\Http\Controllers\Web;

use App\Domain\Catalog\Services\SearchService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));
        $products = $term !== '' ? $this->search->products($term, 40) : collect();

        if ($term !== '') {
            $this->search->log($term, $products->count(), $request->user()?->id, $request->ip());
        }

        return view('web.search', [
            'term' => $term,
            'products' => $products,
            'trending' => $this->search->trending(),
        ]);
    }

    /** Type-ahead. Deliberately capped and cheap: it runs on every keystroke. */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        return response()->json([
            'status' => 'ok',
            'data' => $this->search->suggest($term),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/** Feeds the dependent state → district → city selects used across the product. */
class GeoController extends Controller
{
    public function states(): JsonResponse
    {
        // Cached as plain arrays: a serialised Eloquent Collection returns from a
        // persistent cache store as an incomplete class.
        $states = Cache::remember('geo.states', now()->addDay(), fn () => State::active()
            ->orderBy('name')->get(['id', 'name', 'slug'])->toArray());

        return response()->json(['status' => 'ok', 'data' => $states]);
    }

    public function districts(State $state): JsonResponse
    {
        $districts = Cache::remember("geo.districts.{$state->id}", now()->addDay(), fn () => $state->districts()
            ->active()->orderBy('name')->get(['id', 'name', 'slug'])->toArray());

        return response()->json(['status' => 'ok', 'data' => $districts]);
    }

    public function cities(District $district): JsonResponse
    {
        $cities = Cache::remember("geo.cities.{$district->id}", now()->addDay(), fn () => $district->cities()
            ->active()->orderBy('name')->get(['id', 'name', 'slug'])->toArray());

        return response()->json(['status' => 'ok', 'data' => $cities]);
    }
}

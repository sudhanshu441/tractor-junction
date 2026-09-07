<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\UsedListing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('profile');
        $listingIds = UsedListing::where('user_id', $user->id)->pluck('id');

        return view('account.dashboard', [
            'user' => $user,
            'stats' => [
                'live' => UsedListing::where('user_id', $user->id)->where('status', 'live')->count(),
                'pending' => UsedListing::where('user_id', $user->id)->where('status', 'pending')->count(),
                'buyers' => Lead::where('leadable_type', (new UsedListing)->getMorphClass())
                    ->whereIn('leadable_id', $listingIds)->count(),
                'enquiries' => Lead::where('user_id', $user->id)->count(),
            ],
            'recentListings' => UsedListing::with('images')->where('user_id', $user->id)
                ->latest()->take(3)->get(),
            'recentLeads' => Lead::where('leadable_type', (new UsedListing)->getMorphClass())
                ->whereIn('leadable_id', $listingIds)->latest()->take(5)->get(),
        ]);
    }
}

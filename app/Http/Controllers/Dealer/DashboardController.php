<?php

namespace App\Http\Controllers\Dealer;

use App\Domain\Dealer\Services\DealerService;
use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DealerService $dealers) {}

    public function index(Request $request): View
    {
        $dealer = $this->currentDealer($request);

        if (! $dealer) {
            return view('dealer.no-profile');
        }

        $assignments = $dealer->leadAssignments();

        return view('dealer.dashboard', [
            'dealer' => $dealer->load('brands', 'branches'),
            'usage' => $this->dealers->planUsage($dealer),
            'stats' => [
                'unanswered' => (clone $assignments)->where('status', 'pending')->count(),
                'this_month' => (clone $assignments)
                    ->whereBetween('assigned_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'contacted' => Lead::whereIn('id', (clone $assignments)->select('lead_id'))
                    ->whereIn('status', ['contacted', 'qualified', 'converted'])->count(),
                'converted' => Lead::whereIn('id', (clone $assignments)->select('lead_id'))
                    ->where('status', 'converted')->count(),
                'inventory' => $dealer->inventory()->where('is_active', true)->count(),
                'listings' => $dealer->usedListings()->live()->count(),
            ],
            'recentLeads' => Lead::whereIn('id', (clone $assignments)->select('lead_id'))
                ->with('leadable')->latest()->take(6)->get(),
        ]);
    }

    /**
     * The dealer this user works for — as owner or as staff. Every dealer-panel
     * controller resolves it this way so a staff member sees the same inbox.
     */
    public static function resolveDealer(Request $request): ?Dealer
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return Dealer::where('owner_user_id', $user->id)->first()
            ?? Dealer::whereHas('staff', fn ($q) => $q->where('user_id', $user->id)->where('is_active', true))->first();
    }

    private function currentDealer(Request $request): ?Dealer
    {
        return self::resolveDealer($request);
    }
}

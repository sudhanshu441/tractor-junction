<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\DealerInventory;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $dealer = DashboardController::resolveDealer($request);

        abort_unless($dealer, 403);

        $limit = (int) ($dealer->activeSubscription?->plan?->inventory_limit ?? 0);

        return view('dealer.inventory', [
            'dealer' => $dealer,
            'inventory' => $dealer->inventory()->with('product.brand', 'branch')->paginate(25),
            // An authorised dealer only stocks the brands they represent.
            'options' => Product::with('brand')->active()->available()
                ->when($dealer->brands->isNotEmpty(),
                    fn ($q) => $q->whereIn('brand_id', $dealer->brands->pluck('id')))
                ->orderBy('name')->limit(300)->get(),
            'branches' => $dealer->branches,
            'limit' => $limit,
            'used' => $dealer->inventory()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dealer = DashboardController::resolveDealer($request);

        abort_unless($dealer, 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'dealer_branch_id' => ['nullable', 'exists:dealer_branches,id'],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:999'],
            'offer_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'availability' => ['required', 'in:in_stock,out_of_stock,on_order'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $limit = (int) ($dealer->activeSubscription?->plan?->inventory_limit ?? 0);

        if ($limit > 0 && $dealer->inventory()->count() >= $limit
            && ! $dealer->inventory()->where('product_id', $data['product_id'])->exists()) {
            return back()->with('error', __('Your plan allows :limit models. Upgrade to add more.', ['limit' => $limit]));
        }

        $dealer->inventory()->updateOrCreate(
            ['product_id' => $data['product_id'], 'product_variant_id' => null],
            [...$data, 'is_active' => true],
        );

        return back()->with('success', __('Inventory updated.'));
    }

    public function destroy(Request $request, DealerInventory $item): JsonResponse
    {
        $dealer = DashboardController::resolveDealer($request);

        abort_unless($dealer && $item->dealer_id === $dealer->id, 403);

        $item->delete();

        return response()->json(['status' => 'ok', 'message' => __('Removed from your inventory.')]);
    }
}

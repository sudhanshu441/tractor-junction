<?php

namespace App\Domain\Catalog\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the price a visitor should see and records every change.
 *
 * A product carries a national row plus optional per-state rows. The most
 * specific active row that is in its effective window wins:
 *   city → state → national.
 */
class PriceService
{
    public function resolve(Product $product, ?int $stateId = null, ?int $cityId = null): ?ProductPrice
    {
        $prices = $product->relationLoaded('prices')
            ? $product->prices
            : $product->prices()->where('is_active', true)->get();

        $inWindow = $prices->filter(fn (ProductPrice $p) => $p->is_active
            && (! $p->effective_from || $p->effective_from->lte(now()))
            && (! $p->effective_to || $p->effective_to->gte(now())));

        return $inWindow->firstWhere(fn (ProductPrice $p) => $cityId && $p->city_id === $cityId)
            ?? $inWindow->firstWhere(fn (ProductPrice $p) => $stateId && $p->state_id === $stateId && $p->city_id === null)
            ?? $inWindow->firstWhere(fn (ProductPrice $p) => $p->state_id === null && $p->city_id === null);
    }

    /** Every state row for the "on-road price in your city" table. */
    public function stateTable(Product $product): Collection
    {
        return $product->prices()
            ->with('state')
            ->where('is_active', true)
            ->whereNotNull('state_id')
            ->whereNull('city_id')
            ->get()
            ->sortBy(fn (ProductPrice $p) => $p->state?->name)
            ->values();
    }

    public function save(Product $product, array $data): ProductPrice
    {
        return DB::transaction(function () use ($product, $data) {
            $existing = $product->prices()
                ->where('state_id', $data['state_id'] ?? null)
                ->where('city_id', $data['city_id'] ?? null)
                ->where('product_variant_id', $data['product_variant_id'] ?? null)
                ->first();

            $oldPrice = $existing?->ex_showroom;

            $price = $product->prices()->updateOrCreate(
                [
                    'state_id' => $data['state_id'] ?? null,
                    'city_id' => $data['city_id'] ?? null,
                    'product_variant_id' => $data['product_variant_id'] ?? null,
                ],
                [
                    'ex_showroom' => $data['ex_showroom'],
                    'rto_charges' => $data['rto_charges'] ?? 0,
                    'insurance_amount' => $data['insurance_amount'] ?? 0,
                    'other_charges' => $data['other_charges'] ?? 0,
                    'effective_from' => $data['effective_from'] ?? now()->toDateString(),
                    'effective_to' => $data['effective_to'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ],
            );

            // Price history is an audit trail, so only real movements are written.
            if ($oldPrice === null || (float) $oldPrice !== (float) $data['ex_showroom']) {
                ProductPriceHistory::create([
                    'product_id' => $product->id,
                    'state_id' => $data['state_id'] ?? null,
                    'old_price' => $oldPrice,
                    'new_price' => $data['ex_showroom'],
                    'changed_by' => Auth::id(),
                ]);
            }

            $this->syncProductRange($product);

            return $price;
        });
    }

    /** Keeps products.price_min/max in step with the rows beneath them. */
    public function syncProductRange(Product $product): void
    {
        $range = $product->prices()->where('is_active', true)
            ->selectRaw('min(ex_showroom) as lo, max(ex_showroom) as hi')->first();

        if ($range?->lo !== null) {
            $product->forceFill(['price_min' => $range->lo, 'price_max' => $range->hi])->saveQuietly();
            $product->refresh();
        }
    }

    /** "₹7.30 Lakh" — how prices are read and spoken in this market. */
    public static function inLakh(?float $amount, int $decimals = 2): string
    {
        if ($amount === null || $amount <= 0) {
            return '—';
        }

        if ($amount >= 10000000) {
            return '₹'.number_format($amount / 10000000, $decimals).' Cr';
        }

        return '₹'.number_format($amount / 100000, $decimals).' Lakh';
    }

    public static function range(?float $min, ?float $max): string
    {
        if (! $min) {
            return __('Price on request');
        }

        if (! $max || (float) $max === (float) $min) {
            return self::inLakh($min);
        }

        return '₹'.number_format($min / 100000, 2).' - '.self::inLakh($max);
    }
}

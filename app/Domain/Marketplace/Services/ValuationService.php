<?php

namespace App\Domain\Marketplace\Services;

use App\Models\Product;
use App\Models\UsedListing;
use App\Models\ValuationRule;
use Illuminate\Support\Facades\Cache;

/**
 * Rule-based fair-price estimate for a used machine.
 *
 *   base × age × hours × condition × region
 *
 * Every multiplier is a row in `valuation_rules`, so operations can retune the
 * model without a deploy. The result is always presented as a RANGE, never a
 * single number — it is an estimate from public data, not an appraisal.
 */
class ValuationService
{
    /** Widen the point estimate by this much either way. */
    private const SPREAD = 0.08;

    public function estimate(UsedListing $listing): ?array
    {
        $base = $this->basePrice($listing);

        if (! $base) {
            return null;
        }

        $rules = $this->rules($listing->category_id);

        $age = max(0, (int) date('Y') - (int) $listing->manufacturing_year);

        $factors = [
            'age' => $this->factor($rules, 'age', $this->ageKey($age), $this->defaultAgeFactor($age)),
            'hours' => $this->factor($rules, 'hours', $this->hoursKey($listing->engine_hours), $this->defaultHoursFactor($listing->engine_hours)),
            'condition' => $this->factor($rules, 'condition', (string) $listing->condition, $this->defaultConditionFactor($listing->condition)),
            'region' => $this->factor($rules, 'region', (string) $listing->state?->code, 1.0),
        ];

        $point = $base * array_product($factors);

        return [
            'base' => round($base),
            'point' => round($point, -2),
            'min' => round($point * (1 - self::SPREAD), -2),
            'max' => round($point * (1 + self::SPREAD), -2),
            'factors' => $factors,
        ];
    }

    /**
     * How far the asking price sits outside the estimated band.
     * Used by the moderation queue to flag listings priced unrealistically.
     *
     * @return array{status: string, deviation_percent: float}|null
     */
    public function assess(UsedListing $listing): ?array
    {
        $estimate = $this->estimate($listing);

        if (! $estimate || ! $listing->expected_price) {
            return null;
        }

        $asking = (float) $listing->expected_price;

        if ($asking >= $estimate['min'] && $asking <= $estimate['max']) {
            return ['status' => 'fair', 'deviation_percent' => 0.0];
        }

        $reference = $asking > $estimate['max'] ? $estimate['max'] : $estimate['min'];
        $deviation = round((($asking - $reference) / max($reference, 1)) * 100, 1);

        return [
            'status' => $asking > $estimate['max'] ? 'above' : 'below',
            'deviation_percent' => $deviation,
        ];
    }

    /** Ex-showroom of the matched catalogue model, or the seller's own price as a floor. */
    private function basePrice(UsedListing $listing): ?float
    {
        if ($listing->product_id) {
            $price = Product::where('id', $listing->product_id)->value('price_min');

            if ($price) {
                return (float) $price;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{factor_type: string, key: string, multiplier: float}>
     *
     * Cached as a plain array. A serialised Collection comes back from a
     * persistent cache store as an incomplete class and takes the page down.
     */
    private function rules(?int $categoryId): array
    {
        return Cache::remember('valuation.rules.'.($categoryId ?? 'all'), now()->addHour(),
            fn () => ValuationRule::where('is_active', true)
                ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', $categoryId))
                ->get(['factor_type', 'key', 'multiplier'])
                ->map(fn ($r) => [
                    'factor_type' => (string) $r->factor_type,
                    'key' => (string) $r->key,
                    'multiplier' => (float) $r->multiplier,
                ])
                ->all());
    }

    private function factor(array $rules, string $type, ?string $key, float $default): float
    {
        if (! $key) {
            return $default;
        }

        foreach ($rules as $rule) {
            if ($rule['factor_type'] === $type && strcasecmp($rule['key'], $key) === 0) {
                return (float) $rule['multiplier'];
            }
        }

        return $default;
    }

    private function ageKey(int $age): string
    {
        return 'year_'.min($age, 15);
    }

    private function hoursKey(?int $hours): string
    {
        return match (true) {
            $hours === null => 'unknown',
            $hours < 1000 => '0-1000',
            $hours < 2000 => '1000-2000',
            $hours < 3500 => '2000-3500',
            $hours < 5000 => '3500-5000',
            default => '5000-plus',
        };
    }

    /** Straight-line-ish depreciation, steeper in the first three years. */
    private function defaultAgeFactor(int $age): float
    {
        return match (true) {
            $age <= 0 => 0.92,
            $age === 1 => 0.85,
            $age === 2 => 0.78,
            $age === 3 => 0.71,
            $age <= 6 => max(0.45, 0.71 - ($age - 3) * 0.06),
            $age <= 10 => max(0.32, 0.53 - ($age - 6) * 0.05),
            default => 0.28,
        };
    }

    private function defaultHoursFactor(?int $hours): float
    {
        return match ($this->hoursKey($hours)) {
            '0-1000' => 1.05,
            '1000-2000' => 1.00,
            '2000-3500' => 0.94,
            '3500-5000' => 0.87,
            '5000-plus' => 0.78,
            default => 0.95,
        };
    }

    private function defaultConditionFactor(?string $condition): float
    {
        return match ($condition) {
            'excellent' => 1.08,
            'good' => 1.00,
            'average' => 0.90,
            'needs_repair' => 0.75,
            default => 0.95,
        };
    }
}

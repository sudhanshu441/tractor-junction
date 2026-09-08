<?php

namespace App\Http\Controllers\Web;

use App\Domain\Finance\Services\EmiCalculator;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\EmiCalculation;
use App\Models\Lender;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmiController extends Controller
{
    public function __construct(private readonly EmiCalculator $emi) {}

    /** Generic calculator, and the per-model variant at /loan/emi-calculator/{brand}/{model}. */
    public function index(Request $request, ?string $brandSlug = null, ?string $productSlug = null): View
    {
        $product = null;

        if ($brandSlug && $productSlug) {
            $brand = Brand::active()->where('slug', $brandSlug)->firstOrFail();
            $product = Product::active()->where('brand_id', $brand->id)
                ->where('slug', $productSlug)->firstOrFail();
        }

        $price = (float) ($request->query('price') ?: $product?->price_min ?: 700000);
        $downPercent = (int) config('kj.finance.default_down_payment_percent');
        $downPayment = (float) ($request->query('down') ?: round($price * $downPercent / 100, -2));
        $rate = (float) ($request->query('rate') ?: config('kj.finance.default_interest_rate'));
        $tenure = (int) ($request->query('tenure') ?: 60);
        $frequency = $request->query('frequency', 'monthly');

        if (! array_key_exists($frequency, EmiCalculator::FREQUENCIES)) {
            $frequency = 'monthly';
        }

        return view('web.finance.emi', [
            'product' => $product,
            'result' => $this->emi->calculate($price, $downPayment, $rate, $tenure, $frequency),
            'schedule' => $this->emi->amortisation($price, $downPayment, $rate, $tenure, $frequency),
            'inputs' => compact('price', 'downPayment', 'rate', 'tenure', 'frequency'),
            'lenders' => Lender::where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::with('brand')->active()->available()
                ->whereHas('category', fn ($q) => $q->where('type', 'tractor'))
                ->orderByDesc('popularity_score')->limit(60)->get(),
        ]);
    }

    /** Live recalculation. The stored figure is always this one, not the browser's. */
    public function calculate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:10000', 'max:99999999'],
            'down_payment' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'rate' => ['required', 'numeric', 'min:0', 'max:36'],
            'tenure' => ['required', 'integer', 'min:'.config('kj.finance.min_tenure_months'), 'max:'.config('kj.finance.max_tenure_months')],
            'frequency' => ['nullable', 'in:monthly,quarterly,half_yearly,yearly'],
            'product_id' => ['nullable', 'exists:products,id'],
        ], [
            'down_payment.lte' => __('Down payment cannot be more than the price.'),
        ]);

        $frequency = $data['frequency'] ?? 'monthly';

        $result = $this->emi->calculate(
            (float) $data['price'], (float) ($data['down_payment'] ?? 0),
            (float) $data['rate'], (int) $data['tenure'], $frequency,
        );

        EmiCalculation::create([
            'user_id' => $request->user()?->id,
            'product_id' => $data['product_id'] ?? null,
            'price' => $data['price'],
            'down_payment' => $data['down_payment'] ?? 0,
            'interest_rate' => $data['rate'],
            'tenure_months' => $data['tenure'],
            'frequency' => $frequency,
            'emi' => $result['emi'],
            'total_interest' => $result['total_interest'],
            'total_payable' => $result['total_payable'],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'ok',
            'data' => [
                ...$result,
                'schedule' => $this->emi->amortisation(
                    (float) $data['price'], (float) ($data['down_payment'] ?? 0),
                    (float) $data['rate'], (int) $data['tenure'], $frequency,
                ),
                'share_url' => route('emi.index', array_filter([
                    'price' => $data['price'],
                    'down' => $data['down_payment'] ?? null,
                    'rate' => $data['rate'],
                    'tenure' => $data['tenure'],
                    'frequency' => $frequency,
                ])),
            ],
        ]);
    }
}

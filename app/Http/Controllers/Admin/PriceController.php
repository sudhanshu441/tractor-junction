<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Services\PriceService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceController extends Controller
{
    public function __construct(private readonly PriceService $prices) {}

    public function index(Product $product): View
    {
        return view('admin.prices.index', [
            'product' => $product->load('prices.state'),
            'states' => State::active()->orderBy('name')->get(),
            'history' => $product->priceHistory()->with('state')->latest()->limit(30)->get(),
        ]);
    }

    /** AJAX save from the price matrix — one row per state. */
    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'state_id' => ['nullable', 'exists:states,id'],
            'ex_showroom' => ['required', 'numeric', 'min:1000', 'max:99999999'],
            'rto_charges' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'insurance_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'other_charges' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'effective_from' => ['nullable', 'date'],
        ]);

        $price = $this->prices->save($product, $data);

        activity()->performedOn($product)->causedBy($request->user())
            ->withProperties(['state_id' => $data['state_id'] ?? null, 'ex_showroom' => $data['ex_showroom']])
            ->log('Updated price');

        return response()->json([
            'status' => 'ok',
            'message' => __('Price saved.'),
            'data' => [
                'id' => $price->id,
                'on_road_price' => (float) $price->on_road_price,
                'on_road_display' => PriceService::inLakh((float) $price->on_road_price),
                'range' => PriceService::range($product->fresh()->price_min, $product->fresh()->price_max),
            ],
        ]);
    }

    public function destroy(Request $request, Product $product, ProductPrice $price): JsonResponse
    {
        abort_unless($price->product_id === $product->id, 404);

        $price->delete();
        $this->prices->syncProductRange($product);

        return response()->json(['status' => 'ok', 'message' => __('Price row removed.')]);
    }

    /**
     * Bulk CSV import: product_slug,state_code,ex_showroom,rto,insurance,other
     * Rows that cannot be matched are reported rather than silently skipped.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $handle = fopen($request->file('file')->getRealPath(), 'rb');
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), (array) fgetcsv($handle));

        $states = State::pluck('id', 'code');
        $imported = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            $row = array_combine($header, array_pad($row, count($header), null));

            $product = Product::where('slug', trim((string) ($row['product_slug'] ?? '')))->first();

            if (! $product) {
                $errors[] = __('Line :n: unknown product ":slug"', ['n' => $line, 'slug' => $row['product_slug'] ?? '']);

                continue;
            }

            $stateCode = strtoupper(trim((string) ($row['state_code'] ?? '')));

            if ($stateCode !== '' && ! isset($states[$stateCode])) {
                $errors[] = __('Line :n: unknown state code ":code"', ['n' => $line, 'code' => $stateCode]);

                continue;
            }

            if (! is_numeric($row['ex_showroom'] ?? null)) {
                $errors[] = __('Line :n: ex_showroom must be a number', ['n' => $line]);

                continue;
            }

            $this->prices->save($product, [
                'state_id' => $stateCode !== '' ? $states[$stateCode] : null,
                'ex_showroom' => (float) $row['ex_showroom'],
                'rto_charges' => (float) ($row['rto'] ?? 0),
                'insurance_amount' => (float) ($row['insurance'] ?? 0),
                'other_charges' => (float) ($row['other'] ?? 0),
            ]);

            $imported++;
        }

        fclose($handle);

        activity()->causedBy($request->user())
            ->withProperties(['imported' => $imported, 'errors' => count($errors)])
            ->log('Imported prices');

        return back()
            ->with('success', __(':count price rows imported.', ['count' => $imported]))
            ->with('import_errors', array_slice($errors, 0, 25));
    }
}

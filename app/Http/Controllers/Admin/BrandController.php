<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('admin.brands.index');
    }

    public function data(Request $request): JsonResponse
    {
        $query = Brand::withCount('products');

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $total = Brand::count();
        $filtered = (clone $query)->count();

        $columns = ['name', 'products_count', 'sort_order', 'is_popular', 'is_active'];
        $orderCol = $columns[$request->input('order.0.column', 2)] ?? 'sort_order';
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $rows = $query->orderBy($orderCol, $orderDir)
            ->skip((int) $request->input('start', 0))
            ->take(min(100, (int) $request->input('length', 25)))
            ->get()
            ->map(fn (Brand $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'logo' => $b->logo,
                'products_count' => $b->products_count,
                'sort_order' => $b->sort_order,
                'is_popular' => $b->is_popular,
                'is_active' => $b->is_active,
                'edit_url' => route('admin.brands.edit', $b),
                'toggle_url' => route('admin.brands.toggle', $b),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ]);
    }

    public function create(): View
    {
        return view('admin.brands.form', ['brand' => new Brand(['is_active' => true])]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = Brand::create($this->payload($request));

        activity()->performedOn($brand)->causedBy($request->user())->log('Created brand');

        return redirect()->route('admin.brands.index')
            ->with('success', __('Brand :name created.', ['name' => $brand->name]));
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.form', ['brand' => $brand]);
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($this->payload($request, $brand));

        activity()->performedOn($brand)->causedBy($request->user())->log('Updated brand');

        return redirect()->route('admin.brands.index')
            ->with('success', __('Brand :name updated.', ['name' => $brand->name]));
    }

    public function toggle(Request $request, Brand $brand): JsonResponse
    {
        $brand->update(['is_active' => ! $brand->is_active]);

        activity()->performedOn($brand)->causedBy($request->user())
            ->log($brand->is_active ? 'Activated brand' : 'Deactivated brand');

        return response()->json([
            'status' => 'ok',
            'message' => $brand->is_active ? __('Brand activated.') : __('Brand hidden from the site.'),
            'data' => ['is_active' => $brand->is_active],
        ]);
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return back()->with('error', __('Cannot delete :name — it still has products. Deactivate it instead.', ['name' => $brand->name]));
        }

        $brand->delete();
        activity()->performedOn($brand)->causedBy($request->user())->log('Deleted brand');

        return redirect()->route('admin.brands.index')->with('success', __('Brand deleted.'));
    }

    private function payload(BrandRequest $request, ?Brand $brand = null): array
    {
        $data = $request->validated();

        // The slug is the public URL, so it is only generated once and then frozen.
        $data['slug'] = $brand?->slug ?: Str::slug($data['name']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        } else {
            unset($data['logo']);
        }

        return $data;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Services\FilterCacheService;
use App\Http\Controllers\Controller;
use App\Models\SpecAttribute;
use App\Models\SpecGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Spec groups and attributes together define what a product form asks for.
 * Adding an attribute here changes the catalogue without a migration.
 */
class SpecController extends Controller
{
    public function __construct(private readonly FilterCacheService $filterCache) {}

    public function index(): View
    {
        return view('admin.specs.index', [
            'groups' => SpecGroup::with(['attributes' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:spec_groups,name'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        SpecGroup::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', __('Specification group added.'));
    }

    public function storeAttribute(Request $request): RedirectResponse
    {
        $data = $this->validateAttribute($request);

        SpecAttribute::create([...$data, 'slug' => Str::slug($data['name'])]);

        $this->filterCache->flushFacets();

        return back()->with('success', __('Specification added.'));
    }

    public function updateAttribute(Request $request, SpecAttribute $attribute): RedirectResponse
    {
        $attribute->update($this->validateAttribute($request));

        // A filterable flag or unit change alters every facet on the site.
        $this->filterCache->flushFacets();

        return back()->with('success', __('Specification updated.'));
    }

    public function destroyAttribute(SpecAttribute $attribute): RedirectResponse
    {
        if ($attribute->values()->exists()) {
            return back()->with('error', __('This specification is in use by products and cannot be deleted.'));
        }

        $attribute->delete();
        $this->filterCache->flushFacets();

        return back()->with('success', __('Specification deleted.'));
    }

    private function validateAttribute(Request $request): array
    {
        return $request->validate([
            'spec_group_id' => ['required', 'exists:spec_groups,id'],
            'name' => ['required', 'string', 'max:100'],
            'data_type' => ['required', 'in:int,decimal,string,boolean,select,json'],
            'unit' => ['nullable', 'string', 'max:20'],
            'is_filterable' => ['boolean'],
            'is_comparable' => ['boolean'],
            'is_key_spec' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}

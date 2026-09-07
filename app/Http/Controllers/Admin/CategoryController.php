<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Models\SpecAttribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        // Rendered as a tree, so the whole (small) set is loaded at once.
        return view('admin.categories.index', [
            'categories' => Category::withCount('products')->orderBy('type')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category(['is_active' => true, 'type' => 'tractor']),
            'parents' => Category::orderBy('name')->get(),
            'attributes' => SpecAttribute::with('group')->orderBy('sort_order')->get()->groupBy('group.name'),
            'assigned' => [],
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $category = Category::create($data);
        $category->specAttributes()->sync($this->attributePivot($request));

        activity()->performedOn($category)->causedBy($request->user())->log('Created category');

        return redirect()->route('admin.categories.index')
            ->with('success', __('Category :name created.', ['name' => $category->name]));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::where('id', '!=', $category->id)->orderBy('name')->get(),
            'attributes' => SpecAttribute::with('group')->orderBy('sort_order')->get()->groupBy('group.name'),
            'assigned' => $category->specAttributes->pluck('id')->all(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());
        $category->specAttributes()->sync($this->attributePivot($request));

        activity()->performedOn($category)->causedBy($request->user())->log('Updated category');

        return redirect()->route('admin.categories.index')
            ->with('success', __('Category :name updated.', ['name' => $category->name]));
    }

    public function toggle(Request $request, Category $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return response()->json([
            'status' => 'ok',
            'message' => $category->is_active ? __('Category activated.') : __('Category hidden.'),
            'data' => ['is_active' => $category->is_active],
        ]);
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->with('error', __('Cannot delete a category that still has products or sub-categories.'));
        }

        $category->delete();
        activity()->performedOn($category)->causedBy($request->user())->log('Deleted category');

        return redirect()->route('admin.categories.index')->with('success', __('Category deleted.'));
    }

    /** Attribute ids => pivot data, preserving the order the admin chose. */
    private function attributePivot(CategoryRequest $request): array
    {
        return collect($request->input('spec_attributes', []))
            ->values()
            ->mapWithKeys(fn ($id, $index) => [(int) $id => ['sort_order' => $index, 'is_required' => false]])
            ->all();
    }
}

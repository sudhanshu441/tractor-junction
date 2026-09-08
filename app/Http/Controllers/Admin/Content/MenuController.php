<?php

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Services\ContentService;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The menu builder. Two levels only — a third level of dropdown is unusable on
 * the phones most of this audience browses on.
 */
class MenuController extends Controller
{
    public function index(Request $request, ?string $slug = null): View
    {
        $menus = Menu::orderBy('name')->get();
        $menu = $slug
            ? Menu::where('slug', $slug)->firstOrFail()
            : $menus->first();

        return view('admin.content.menus', [
            'menus' => $menus,
            'menu' => $menu,
            'items' => $menu
                ? $menu->rootItems()->with('children')->get()
                : collect(),
        ]);
    }

    public function storeItem(Request $request, Menu $menu): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'icon' => ['nullable', 'string', 'max:60'],
        ]);

        if ($data['parent_id'] ?? null) {
            $parent = MenuItem::findOrFail($data['parent_id']);

            if ($parent->parent_id !== null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Menus go two levels deep. A third level cannot be tapped on a phone.'),
                ], 422);
            }
        }

        $menu->items()->create([
            ...$data,
            'sort_order' => (int) $menu->items()->max('sort_order') + 1,
            'is_active' => true,
        ]);

        ContentService::flush();

        return response()->json(['status' => 'ok', 'message' => __('Item added.')]);
    }

    public function updateItem(Request $request, MenuItem $item): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $item->update([...$data, 'is_active' => (bool) ($data['is_active'] ?? false)]);

        ContentService::flush();

        return response()->json(['status' => 'ok', 'message' => __('Item saved.')]);
    }

    public function destroyItem(MenuItem $item): JsonResponse
    {
        $item->delete();   // children cascade
        ContentService::flush();

        return response()->json(['status' => 'ok', 'message' => __('Item removed.')]);
    }

    /** Drag-to-reorder posts the whole order at once. */
    public function reorder(Request $request, Menu $menu): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        DB::transaction(function () use ($menu, $data) {
            foreach ($data['order'] as $position => $id) {
                $menu->items()->where('id', $id)->update(['sort_order' => $position]);
            }
        });

        ContentService::flush();

        return response()->json(['status' => 'ok', 'message' => __('Order saved.')]);
    }
}

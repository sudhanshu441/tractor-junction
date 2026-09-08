<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BlogCategory;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD for the small editor-managed masters: FAQs, banners, testimonials, blog
 * categories and videos.
 *
 * These five differ only in their columns, so eight near-identical controllers
 * would be five hundred lines of copy with five places to forget an activity
 * log. The shape of each one lives in one readable map below; anything with
 * real behaviour (posts, pages, offers, menus) keeps its own controller.
 */
class MasterController extends Controller
{
    /**
     * @var array<string, array{
     *     model: class-string<Model>, label: string, permission: string,
     *     rules: array<string, mixed>, columns: array<int, string>,
     *     slug_from?: string, order?: string, uploads?: array<int, string>
     * }>
     */
    public const RESOURCES = [
        'faqs' => [
            'model' => Faq::class,
            'label' => 'FAQ',
            'permission' => 'faqs',
            'order' => 'sort_order',
            'columns' => ['category', 'question', 'sort_order', 'is_active'],
            'rules' => [
                'category' => ['required', 'string', 'max:40'],
                'question' => ['required', 'string', 'max:255'],
                'answer' => ['required', 'string', 'max:5000'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ],
        ],
        'banners' => [
            'model' => Banner::class,
            'label' => 'Banner',
            'permission' => 'banners',
            'order' => 'sort_order',
            'columns' => ['position', 'title', 'device', 'sort_order', 'is_active'],
            'uploads' => ['image_desktop', 'image_mobile'],
            'rules' => [
                'position' => ['required', 'in:home_slider,home_mid,listing_top,sidebar,detail_bottom,app_promo'],
                'title' => ['nullable', 'string', 'max:255'],
                'subtitle' => ['nullable', 'string', 'max:255'],
                'image_desktop' => ['nullable', 'image', 'max:4096'],
                'image_mobile' => ['nullable', 'image', 'max:4096'],
                'cta_text' => ['nullable', 'string', 'max:60'],
                'cta_url' => ['nullable', 'string', 'max:255'],
                'device' => ['required', 'in:all,desktop,mobile'],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ],
        ],
        'testimonials' => [
            'model' => Testimonial::class,
            'label' => 'Testimonial',
            'permission' => 'testimonials',
            'order' => 'sort_order',
            'columns' => ['name', 'city', 'rating', 'sort_order', 'is_active'],
            'uploads' => ['photo'],
            'rules' => [
                'name' => ['required', 'string', 'max:100'],
                'designation' => ['nullable', 'string', 'max:100'],
                'city' => ['nullable', 'string', 'max:100'],
                'photo' => ['nullable', 'image', 'max:2048'],
                'message' => ['required', 'string', 'max:1000'],
                'rating' => ['required', 'integer', 'min:1', 'max:5'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ],
        ],
        'blog-categories' => [
            'model' => BlogCategory::class,
            'label' => 'Blog category',
            'permission' => 'blogs',
            'order' => 'sort_order',
            'slug_from' => 'name',
            'columns' => ['name', 'slug', 'sort_order', 'is_active'],
            'rules' => [
                'name' => ['required', 'string', 'max:100'],
                'icon' => ['nullable', 'string', 'max:60'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ],
        ],
        'videos' => [
            'model' => Video::class,
            'label' => 'Video',
            'permission' => 'videos',
            'order' => 'id',
            'slug_from' => 'title',
            'columns' => ['title', 'youtube_id', 'view_count', 'is_featured', 'is_active'],
            'rules' => [
                'title' => ['required', 'string', 'max:200'],
                'youtube_id' => ['required', 'string', 'max:30'],
                'product_id' => ['nullable', 'exists:products,id'],
                'description' => ['nullable', 'string', 'max:2000'],
                'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
                'is_featured' => ['nullable', 'boolean'],
                'is_active' => ['nullable', 'boolean'],
            ],
        ],
    ];

    public function index(Request $request, string $resource): View
    {
        $config = $this->config($resource);
        $model = $config['model'];

        return view('admin.content.masters.index', [
            'resource' => $resource,
            'config' => $config,
            'rows' => $model::query()
                ->orderBy($config['order'] ?? 'id', ($config['order'] ?? 'id') === 'id' ? 'desc' : 'asc')
                ->paginate(30),
        ]);
    }

    public function create(string $resource): View
    {
        $config = $this->config($resource);

        return $this->form($resource, $config, new $config['model']);
    }

    public function edit(string $resource, int $id): View
    {
        $config = $this->config($resource);

        return $this->form($resource, $config, $config['model']::findOrFail($id));
    }

    private function form(string $resource, array $config, Model $row): View
    {
        return view('admin.content.masters.form', [
            'resource' => $resource,
            'config' => $config,
            'row' => $row,
            'products' => $resource === 'videos'
                ? Product::with('brand')->where('is_active', true)->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $config = $this->config($resource);
        $row = $config['model']::create($this->payload($request, $config, new $config['model']));

        activity()->performedOn($row)->causedBy($request->user())->log('Created '.strtolower($config['label']));

        return redirect()->route('admin.content.index', $resource)
            ->with('success', __(':label created.', ['label' => $config['label']]));
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $config = $this->config($resource);
        $row = $config['model']::findOrFail($id);

        $row->update($this->payload($request, $config, $row));

        activity()->performedOn($row)->causedBy($request->user())->log('Updated '.strtolower($config['label']));

        return redirect()->route('admin.content.index', $resource)
            ->with('success', __(':label saved.', ['label' => $config['label']]));
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        $config = $this->config($resource);
        $row = $config['model']::findOrFail($id);
        $row->delete();

        activity()->performedOn($row)->causedBy($request->user())->log('Deleted '.strtolower($config['label']));

        return back()->with('success', __(':label deleted.', ['label' => $config['label']]));
    }

    private function payload(Request $request, array $config, Model $row): array
    {
        $data = $request->validate($config['rules']);

        foreach ($config['uploads'] ?? [] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('content', 'public');
            } else {
                // An unchanged file input must not blank the stored path.
                unset($data[$field]);
            }
        }

        foreach (['is_active', 'is_featured'] as $flag) {
            if (array_key_exists($flag, $config['rules'])) {
                $data[$flag] = (bool) ($data[$flag] ?? false);
            }
        }

        if ($source = $config['slug_from'] ?? null) {
            $data['slug'] = $this->uniqueSlug($config['model'], Str::slug($data[$source]), $row);
        }

        return $data;
    }

    private function uniqueSlug(string $model, string $slug, Model $row): string
    {
        $base = $slug;
        $i = 1;

        while ($model::where('slug', $slug)->where('id', '!=', $row->id ?? 0)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    /** @return array<string, mixed> */
    private function config(string $resource): array
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }
}

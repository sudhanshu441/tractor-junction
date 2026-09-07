@extends('layouts.admin')

@section('title', ($product->exists ? __('Edit product') : __('Add product')).' — Krishi Junction Admin')
@section('page_title', $product->exists ? $product->full_name : __('Add product'))

@section('content')
<form method="POST" enctype="multipart/form-data"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <ul class="nav nav-pills small" id="productTabs" role="tablist">
            @foreach ([
                'basics' => __('Basics'), 'specs' => __('Specifications'), 'prices' => __('Prices'),
                'media' => __('Images'), 'features' => __('Features'), 'faqs' => __('FAQs'),
                'videos' => __('Videos'), 'competitors' => __('Competitors'),
            ] as $key => $label)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="pill"
                            data-bs-target="#tab-{{ $key }}" type="button" role="tab">{{ $label }}</button>
                </li>
            @endforeach
        </ul>

        <div class="d-flex gap-2">
            @if ($product->exists && $product->brand)
                <a href="{{ route('products.show', [$product->brand->slug, $product->slug]) }}"
                   target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">{{ __('View on site') }}</a>
            @endif
            <button class="btn btn-sm btn-primary" type="submit">
                {{ $product->exists ? __('Save product') : __('Create product') }}
            </button>
        </div>
    </div>

    <div class="tab-content">
        {{-- ---------------- Basics ---------------- --}}
        <div class="tab-pane fade show active" id="tab-basics" role="tabpanel">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card"><div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="brand_id">{{ __('Brand') }}</label>
                                <select id="brand_id" name="brand_id" class="form-select @error('brand_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select brand') }}</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('brand_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="category_id">{{ __('Category') }}</label>
                                <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select category') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                            {{ $category->name }} ({{ $category->type }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('Decides which specification fields appear.') }}</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="model_code">{{ __('Model code') }}</label>
                                <input type="text" id="model_code" name="model_code" class="form-control"
                                       value="{{ old('model_code', $product->model_code) }}">
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="name">{{ __('Model name') }}</label>
                                <input type="text" id="name" name="name" required maxlength="150"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $product->name) }}" placeholder="575 DI XP Plus">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if ($product->exists)
                                    <div class="form-text mono">/tractors/{{ $product->brand?->slug }}/{{ $product->slug }}</div>
                                @endif
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="status">{{ __('Availability') }}</label>
                                <select id="status" name="status" class="form-select">
                                    @foreach (['available', 'upcoming', 'discontinued'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $product->status) === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="hp_min">{{ __('HP (min)') }}</label>
                                <input type="number" step="0.1" id="hp_min" name="hp_min"
                                       class="form-control @error('hp_min') is-invalid @enderror"
                                       value="{{ old('hp_min', $product->hp_min) }}">
                                @error('hp_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('Derived from the "Engine HP" specification when one is set.') }}</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="hp_max">{{ __('HP (max)') }}</label>
                                <input type="number" step="0.1" id="hp_max" name="hp_max"
                                       class="form-control @error('hp_max') is-invalid @enderror"
                                       value="{{ old('hp_max', $product->hp_max) }}">
                                @error('hp_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('Leave blank if a single rating.') }}</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="launch_year">{{ __('Launch year') }}</label>
                                <input type="number" id="launch_year" name="launch_year" min="1950" max="{{ date('Y') + 3 }}"
                                       class="form-control" value="{{ old('launch_year', $product->launch_year) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="expected_launch_date">{{ __('Expected launch') }}</label>
                                <input type="date" id="expected_launch_date" name="expected_launch_date" class="form-control"
                                       value="{{ old('expected_launch_date', $product->expected_launch_date?->toDateString()) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="short_description">{{ __('Short description') }}</label>
                                <textarea id="short_description" name="short_description" rows="2" maxlength="500"
                                          class="form-control">{{ old('short_description', $product->short_description) }}</textarea>
                                <div class="form-text">{{ __('Used as the meta description when no SEO override is set.') }}</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="description">{{ __('Full description') }}</label>
                                <textarea id="description" name="description" rows="8"
                                          class="form-control">{{ old('description', $product->description) }}</textarea>
                            </div>
                        </div>
                    </div></div>
                </div>

                <div class="col-lg-4">
                    <div class="card"><div class="card-body">
                        <h2 class="h6 mb-3">{{ __('Publishing') }}</h2>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active"
                                   value="1" @checked(old('is_active', $product->is_active ?? true))>
                            <label class="form-check-label" for="is_active">{{ __('Published') }}</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_popular" name="is_popular"
                                   value="1" @checked(old('is_popular', $product->is_popular))>
                            <label class="form-check-label" for="is_popular">{{ __('Popular') }}</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured"
                                   value="1" @checked(old('is_featured', $product->is_featured))>
                            <label class="form-check-label" for="is_featured">{{ __('Featured on home') }}</label>
                        </div>

                        @if ($product->exists)
                            <hr>
                            <div class="d-flex justify-content-between small"><span class="text-muted-2">{{ __('Views') }}</span><span class="mono">{{ number_format($product->view_count) }}</span></div>
                            <div class="d-flex justify-content-between small"><span class="text-muted-2">{{ __('Specs filled') }}</span><span class="mono">{{ $product->specValues->count() }}</span></div>
                            <div class="d-flex justify-content-between small"><span class="text-muted-2">{{ __('Price rows') }}</span><span class="mono">{{ $product->prices->count() }}</span></div>
                            <div class="d-flex justify-content-between small"><span class="text-muted-2">{{ __('Images') }}</span><span class="mono">{{ $product->media->count() }}</span></div>
                        @endif
                    </div></div>
                </div>
            </div>
        </div>

        {{-- ---------------- Specifications ---------------- --}}
        <div class="tab-pane fade" id="tab-specs" role="tabpanel">
            <div class="card"><div class="card-body">
                @if ($specGroups->isEmpty())
                    <div class="alert alert-warning small mb-0">
                        {{ __('This category has no specification fields mapped yet.') }}
                        <a href="{{ route('admin.categories.index') }}">{{ __('Map some on the category') }}</a>.
                    </div>
                @else
                    <p class="small text-muted-2">
                        {{ __('Built from the specifications mapped to this category. Leave a field blank to remove its value.') }}
                    </p>

                    @foreach ($specGroups as $groupName => $attributes)
                        <div class="kj-label mt-4 mb-2">{{ $groupName ?: __('Other') }}</div>
                        <div class="kj-spec-grid">
                            @foreach ($attributes as $attribute)
                                @php $value = $specValues->get($attribute->id); @endphp
                                <div>
                                    <label class="form-label" for="spec-{{ $attribute->id }}">
                                        {{ $attribute->name }}
                                        @if ($attribute->unit)<span class="text-muted-2">({{ $attribute->unit }})</span>@endif
                                    </label>

                                    @if ($attribute->data_type === 'boolean')
                                        <select id="spec-{{ $attribute->id }}" name="specs[{{ $attribute->id }}]" class="form-select form-select-sm">
                                            <option value="">—</option>
                                            <option value="1" @selected($value?->value_boolean === true)>{{ __('Yes') }}</option>
                                            <option value="0" @selected($value?->value_boolean === false)>{{ __('No') }}</option>
                                        </select>
                                    @elseif (in_array($attribute->data_type, ['int', 'decimal']))
                                        <input type="number" step="{{ $attribute->data_type === 'int' ? '1' : '0.01' }}"
                                               id="spec-{{ $attribute->id }}" name="specs[{{ $attribute->id }}]"
                                               class="form-control form-control-sm"
                                               value="{{ $value?->value_number !== null ? rtrim(rtrim((string) $value->value_number, '0'), '.') : '' }}">
                                    @else
                                        <input type="text" id="spec-{{ $attribute->id }}" name="specs[{{ $attribute->id }}]"
                                               class="form-control form-control-sm" value="{{ $value?->value_string }}">
                                    @endif

                                    @if ($attribute->is_filterable)
                                        <div class="form-text">{{ __('Drives a facet on the listing pages.') }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div></div>
        </div>

        {{-- ---------------- Prices ---------------- --}}
        <div class="tab-pane fade" id="tab-prices" role="tabpanel">
            <div class="card"><div class="card-body">
                @if (! $product->exists)
                    <div class="alert alert-secondary small mb-0">{{ __('Create the product first, then add prices.') }}</div>
                @else
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h6 mb-1">{{ __('State-wise prices') }}</h2>
                            <p class="small text-muted-2 mb-0">
                                {{ __('The national row is the fallback. A state row overrides it for visitors in that state.') }}
                            </p>
                        </div>
                        <a href="{{ route('admin.prices.index', $product) }}" class="btn btn-sm btn-outline-primary">
                            {{ __('Open price manager') }}
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Scope') }}</th>
                                    <th class="num">{{ __('Ex-showroom') }}</th>
                                    <th class="num">{{ __('RTO') }}</th>
                                    <th class="num">{{ __('Insurance') }}</th>
                                    <th class="num">{{ __('On-road') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($nationalPrice)
                                    <tr>
                                        <td><b>{{ __('National') }}</b></td>
                                        <td class="num mono">{{ number_format((float) $nationalPrice->ex_showroom) }}</td>
                                        <td class="num mono">{{ number_format((float) $nationalPrice->rto_charges) }}</td>
                                        <td class="num mono">{{ number_format((float) $nationalPrice->insurance_amount) }}</td>
                                        <td class="num mono fw-bold">{{ number_format((float) $nationalPrice->on_road_price) }}</td>
                                    </tr>
                                @endif
                                @forelse ($priceRows as $row)
                                    <tr>
                                        <td>{{ $row->state?->name }}</td>
                                        <td class="num mono">{{ number_format((float) $row->ex_showroom) }}</td>
                                        <td class="num mono">{{ number_format((float) $row->rto_charges) }}</td>
                                        <td class="num mono">{{ number_format((float) $row->insurance_amount) }}</td>
                                        <td class="num mono fw-bold">{{ number_format((float) $row->on_road_price) }}</td>
                                    </tr>
                                @empty
                                    @unless ($nationalPrice)
                                        <tr><td colspan="5" class="small text-muted-2">{{ __('No prices set yet.') }}</td></tr>
                                    @endunless
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div></div>
        </div>

        {{-- ---------------- Images ---------------- --}}
        <div class="tab-pane fade" id="tab-media" role="tabpanel">
            <div class="card"><div class="card-body">
                <label class="form-label" for="images">{{ __('Add images') }}</label>
                <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
                       class="form-control @error('images.*') is-invalid @enderror">
                @error('images.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">
                    {{ __('JPG, PNG or WebP up to 5 MB each. Thumb, card and detail sizes are generated automatically.') }}
                </div>

                @if ($product->exists && $product->media->isNotEmpty())
                    <hr>
                    <div class="row g-2">
                        @foreach ($product->media as $item)
                            <div class="col-6 col-md-3 col-xl-2">
                                <div class="kj-image-tile">
                                    <img src="{{ $item->url('thumb') }}" alt="{{ $item->alt_text }}" loading="lazy">
                                    <div class="actions">
                                        @if ($item->collection === 'primary')
                                            <span class="badge badge-ok w-100 text-center">{{ __('Primary') }}</span>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary flex-fill js-primary-image"
                                                    data-url="{{ route('admin.products.media.primary', [$product, $item]) }}">
                                                {{ __('Primary') }}
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-delete-image"
                                                data-url="{{ route('admin.products.media.destroy', [$product, $item]) }}">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div></div>
        </div>

        {{-- ---------------- Features ---------------- --}}
        <div class="tab-pane fade" id="tab-features" role="tabpanel">
            <div class="card"><div class="card-body">
                <p class="small text-muted-2">{{ __('Highlights shown as a grid on the model page.') }}</p>
                @for ($i = 0; $i < 6; $i++)
                    @php $feature = $product->features[$i] ?? null; @endphp
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <input type="text" name="features[{{ $i }}][title]" class="form-control form-control-sm"
                                   placeholder="{{ __('Feature title') }}" value="{{ $feature->title ?? '' }}">
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="features[{{ $i }}][description]" class="form-control form-control-sm"
                                   placeholder="{{ __('Short description') }}" value="{{ $feature->description ?? '' }}">
                        </div>
                    </div>
                @endfor
            </div></div>
        </div>

        {{-- ---------------- FAQs ---------------- --}}
        <div class="tab-pane fade" id="tab-faqs" role="tabpanel">
            <div class="card"><div class="card-body">
                <p class="small text-muted-2">{{ __('Rendered as an accordion and emitted as FAQPage schema.') }}</p>
                @for ($i = 0; $i < 6; $i++)
                    @php $faq = $product->faqs[$i] ?? null; @endphp
                    <div class="mb-3">
                        <input type="text" name="faqs[{{ $i }}][question]" class="form-control form-control-sm mb-1"
                               placeholder="{{ __('Question') }}" value="{{ $faq->question ?? '' }}">
                        <textarea name="faqs[{{ $i }}][answer]" rows="2" class="form-control form-control-sm"
                                  placeholder="{{ __('Answer') }}">{{ $faq->answer ?? '' }}</textarea>
                    </div>
                @endfor
            </div></div>
        </div>

        {{-- ---------------- Videos ---------------- --}}
        <div class="tab-pane fade" id="tab-videos" role="tabpanel">
            <div class="card"><div class="card-body">
                <p class="small text-muted-2">{{ __('Paste a YouTube URL or the bare video id.') }}</p>
                @for ($i = 0; $i < 4; $i++)
                    @php $video = $product->videos[$i] ?? null; @endphp
                    <div class="row g-2 mb-2">
                        <div class="col-md-5">
                            <input type="text" name="videos[{{ $i }}][title]" class="form-control form-control-sm"
                                   placeholder="{{ __('Video title') }}" value="{{ $video->title ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="videos[{{ $i }}][youtube_id]" class="form-control form-control-sm"
                                   placeholder="https://youtu.be/…" value="{{ $video->youtube_id ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <select name="videos[{{ $i }}][type]" class="form-select form-select-sm">
                                @foreach (['review', 'walkaround', 'comparison', 'demo'] as $type)
                                    <option value="{{ $type }}" @selected(($video->type ?? '') === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endfor
            </div></div>
        </div>

        {{-- ---------------- Competitors ---------------- --}}
        <div class="tab-pane fade" id="tab-competitors" role="tabpanel">
            <div class="card"><div class="card-body">
                <p class="small text-muted-2">
                    {{ __('Editor-picked rivals. They appear on the model page and feed the comparison pages.') }}
                </p>
                @php $current = $product->exists ? $product->competitors->pluck('competitor_product_id')->all() : []; @endphp
                <div class="row g-2">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="col-md-6">
                            <select name="competitors[{{ $i }}]" class="form-select form-select-sm">
                                <option value="">{{ __('— none —') }}</option>
                                @foreach ($competitorOptions as $option)
                                    <option value="{{ $option->id }}" @selected(($current[$i] ?? null) == $option->id)>
                                        {{ $option->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endfor
                </div>
            </div></div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
    // Changing the category changes which spec fields apply, so reload the form.
    $('#category_id').on('change', function () {
        @if ($product->exists)
            if (confirm('{{ __('Reload the form with this category\'s specification fields? Unsaved changes will be lost.') }}')) {
                window.location = '{{ route('admin.products.edit', $product ?? 0) }}?category=' + $(this).val();
            }
        @endif
    });

    $('.js-delete-image').on('click', function () {
        var tile = $(this).closest('.col-6');
        if (!confirm('{{ __('Delete this image?') }}')) { return; }

        KJ.request({ url: $(this).data('url'), method: 'POST', data: { _method: 'DELETE' } })
            .then(function (res) {
                if (res.status === 'ok') { tile.remove(); }
                KJ.toast(res.message, res.status === 'ok' ? 'success' : 'danger');
            });
    });

    $('.js-primary-image').on('click', function () {
        KJ.request({ url: $(this).data('url'), method: 'POST' }).then(function (res) {
            KJ.toast(res.message, 'success');
            window.location.reload();
        });
    });
});
</script>
@endpush

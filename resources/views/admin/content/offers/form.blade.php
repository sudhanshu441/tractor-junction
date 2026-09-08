@extends('layouts.admin')

@section('title', __('Offer — Krishi Junction Admin'))
@section('page_title', $offer->exists ? __('Edit offer') : __('New offer'))

@section('content')
<form method="POST" enctype="multipart/form-data"
      action="{{ $offer->exists ? route('admin.offers.update', $offer) : route('admin.offers.store') }}">
    @csrf
    @if ($offer->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card"><div class="card-body">
                <label class="form-label" for="title">{{ __('Title') }} <span style="color: var(--kj-danger);">*</span></label>
                <input type="text" id="title" name="title" class="form-control mb-3"
                       value="{{ old('title', $offer->title) }}" maxlength="200" required>

                <label class="form-label" for="description">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="form-control mono mb-3" rows="10"
                          style="font-size:.8125rem;">{{ old('description', $offer->description) }}</textarea>

                <label class="form-label" for="terms">{{ __('Terms') }}</label>
                <textarea id="terms" name="terms" class="form-control mb-1" rows="3"
                          maxlength="3000">{{ old('terms', $offer->terms) }}</textarea>
                <p class="small text-muted-2 mb-0">
                    {{ __('Anything the buyer must know before they call a dealer — validity, exclusions, whether finance is required.') }}
                </p>
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-3">{{ __('Save offer') }}</button>

                <label class="form-label" for="brand_id">{{ __('Brand') }}</label>
                <select id="brand_id" name="brand_id" class="form-select mb-3">
                    <option value="">{{ __('All brands') }}</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(old('brand_id', $offer->brand_id) == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>

                <div class="row g-2 mb-3">
                    <div class="col-7">
                        <label class="form-label" for="discount_type">{{ __('Type') }}</label>
                        <select id="discount_type" name="discount_type" class="form-select">
                            @foreach (['flat', 'percent', 'cashback', 'exchange', 'freebie'] as $type)
                                <option value="{{ $type }}" @selected(old('discount_type', $offer->discount_type) === $type)>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label" for="discount_value">{{ __('Value') }}</label>
                        <input type="number" step="0.01" min="0" id="discount_value" name="discount_value"
                               class="form-control" value="{{ old('discount_value', $offer->discount_value) }}">
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label" for="starts_at">{{ __('Starts') }}</label>
                        <input type="date" id="starts_at" name="starts_at" class="form-control"
                               value="{{ old('starts_at', $offer->starts_at?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="ends_at">{{ __('Ends') }}</label>
                        <input type="date" id="ends_at" name="ends_at" class="form-control"
                               value="{{ old('ends_at', $offer->ends_at?->format('Y-m-d')) }}">
                    </div>
                </div>

                <label class="form-label" for="banner_image">{{ __('Banner') }}</label>
                <input type="file" id="banner_image" name="banner_image" class="form-control mb-2" accept="image/*">
                @if ($offer->banner_image)
                    <img src="{{ asset('storage/'.$offer->banner_image) }}" alt="" class="img-fluid rounded mb-2">
                @endif

                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           @checked(old('is_active', $offer->is_active ?? true))>
                    <label class="form-check-label" for="is_active">{{ __('Live on the website') }}</label>
                </div>
            </div></div>

            <div class="card"><div class="card-body">
                <label class="form-label" for="product_ids">{{ __('Models in this offer') }}</label>
                <select id="product_ids" name="product_ids[]" class="form-select" multiple size="12">
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                            @selected(in_array($product->id, old('product_ids', $offer->exists ? $offer->products->pluck('id')->all() : [])))>
                            {{ $product->brand?->name }} {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                <p class="small text-muted-2 mt-1 mb-0">{{ __('Hold Ctrl (or Cmd) to pick several.') }}</p>
            </div></div>
        </div>
    </div>
</form>

@if ($offer->exists)
    <form method="POST" action="{{ route('admin.offers.destroy', $offer) }}" class="mt-3"
          onsubmit="return confirm('{{ __('Delete this offer?') }}')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-secondary"
                style="color: var(--kj-danger); border-color: var(--kj-danger);">{{ __('Delete offer') }}</button>
    </form>
@endif
@endsection

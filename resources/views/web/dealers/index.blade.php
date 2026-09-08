@extends('layouts.app')

@section('title', $heading.' — '.__('Contact & Address | Krishi Junction'))
@section('meta_description', __('Find verified tractor dealers with addresses, brands sold and contact details.'))

@section('content')
<div class="container-xl py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('dealers.index') }}">{{ __('Dealers') }}</a></li>
            @if ($state)<li class="breadcrumb-item active">{{ $district?->name ?? $state->name }}</li>@endif
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $heading }}</h1>
            <p class="text-muted-2 small mb-0">
                {{ trans_choice(':count verified dealer|:count verified dealers', $dealers->total(), ['count' => $dealers->total()]) }}
            </p>
        </div>
        <a href="{{ route('dealers.join') }}" class="btn btn-deep btn-sm">{{ __('Become a dealer') }}</a>
    </div>

    <form method="GET" action="{{ url()->current() }}" class="kj-panel p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="state-picker">{{ __('State') }}</label>
                <select id="state-picker" class="form-select form-select-sm"
                        onchange="if (this.value) window.location = this.value;">
                    <option value="{{ route('dealers.index') }}">{{ __('All India') }}</option>
                    @foreach ($states as $option)
                        <option value="{{ route('dealers.state', $option->slug) }}" @selected($state?->id === $option->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($state)
                <div class="col-md-4">
                    <label class="form-label" for="district-picker">{{ __('District') }}</label>
                    <select id="district-picker" class="form-select form-select-sm"
                            onchange="if (this.value) window.location = this.value;">
                        <option value="{{ route('dealers.state', $state->slug) }}">{{ __('All districts') }}</option>
                        @foreach ($districts as $option)
                            <option value="{{ route('dealers.district', [$state->slug, $option->slug]) }}"
                                @selected($district?->id === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-4">
                <label class="form-label" for="brand">{{ __('Brand') }}</label>
                <select id="brand" name="brand" class="form-select form-select-sm" onchange="this.form.submit();">
                    <option value="">{{ __('All brands') }}</option>
                    @foreach ($brands as $option)
                        <option value="{{ $option->slug }}" @selected($brand?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="row g-3">
        @forelse ($dealers as $dealer)
            <div class="col-md-6 col-xl-4">
                <article class="card h-100"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <h2 class="h6 mb-0">
                            <a href="{{ route('dealers.show', $dealer->slug) }}" class="text-decoration-none text-reset">
                                {{ $dealer->display_name }}
                            </a>
                        </h2>
                        <span class="badge badge-ok">{{ __('Verified') }}</span>
                    </div>

                    <div class="small text-muted-2 mb-2">
                        {{ $dealer->brands->pluck('name')->take(3)->implode(' · ') ?: ucfirst(str_replace('_', ' ', $dealer->dealer_type)) }}
                    </div>

                    <p class="small text-muted-2 mb-2">
                        {{ $dealer->city?->name ?? $dealer->district?->name }}, {{ $dealer->state?->name }}
                    </p>

                    <div class="d-flex justify-content-between align-items-center">
                        @if ($dealer->rating_count > 0)
                            <span class="badge badge-muted">
                                {{ number_format((float) $dealer->rating_avg, 1) }} ★ ({{ $dealer->rating_count }})
                            </span>
                        @else
                            <span class="small text-muted-2">{{ __('No reviews yet') }}</span>
                        @endif

                        <a href="{{ route('dealers.show', $dealer->slug) }}" class="btn btn-sm btn-outline-primary">
                            {{ __('View dealer') }}
                        </a>
                    </div>
                </div></article>
            </div>
        @empty
            <div class="col-12">
                <div class="kj-panel p-5 text-center">
                    <p class="fw-semibold mb-1">{{ __('No verified dealers here yet') }}</p>
                    <p class="small text-muted-2 mb-3">{{ __('Try a neighbouring district, or register your own dealership.') }}</p>
                    <a href="{{ route('dealers.join') }}" class="btn btn-primary">{{ __('Become a dealer') }}</a>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $dealers->links() }}</div>
</div>
@endsection

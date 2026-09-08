@extends('layouts.app')

@section('title', __('Become a Krishi Junction dealer'))
@section('meta_description', __('Register your dealership to receive verified buyer enquiries from your district.'))

@section('content')
<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h1 class="h4 mb-1">{{ __('Become a dealer') }}</h1>
            <p class="text-muted-2 small mb-4">
                {{ __('Register once. We verify your documents, then buyer enquiries from your district start arriving.') }}
            </p>

            @if (session('error'))
                <div class="alert alert-warning small">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger small">
                    <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="row g-3 mb-4">
                @foreach ($plans as $plan)
                    <div class="col-md-4">
                        <div class="card h-100"><div class="card-body">
                            <div class="d-flex justify-content-between align-items-baseline">
                                <b>{{ $plan->name }}</b>
                                <span class="mono">{{ $plan->price > 0 ? '₹'.number_format((float) $plan->price).'/mo' : __('Free') }}</span>
                            </div>
                            <ul class="small text-muted-2 mt-2 mb-0 ps-3">
                                <li>{{ $plan->lead_limit ? __(':n leads a month', ['n' => $plan->lead_limit]) : __('Limited leads') }}</li>
                                <li>{{ __(':n leads a day', ['n' => $plan->daily_lead_cap]) }}</li>
                                <li>{{ __(':n models in inventory', ['n' => $plan->inventory_limit]) }}</li>
                            </ul>
                        </div></div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('dealers.join.store') }}" class="kj-panel p-4">
                @csrf

                <h2 class="h6 mb-3">{{ __('Your dealership') }}</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="business_name">{{ __('Registered business name') }}</label>
                        <input type="text" id="business_name" name="business_name" class="form-control"
                               value="{{ old('business_name') }}" required maxlength="150">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="display_name">{{ __('Name buyers will see') }}</label>
                        <input type="text" id="display_name" name="display_name" class="form-control"
                               value="{{ old('display_name') }}" maxlength="150">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="dealer_type">{{ __('Type of dealership') }}</label>
                        <select id="dealer_type" name="dealer_type" class="form-select" required>
                            @foreach ([
                                'authorised' => __('Authorised dealer'), 'multi_brand' => __('Multi-brand'),
                                'used_only' => __('Used machinery only'), 'implement' => __('Implements'),
                            ] as $v => $l)
                                <option value="{{ $v }}" @selected(old('dealer_type') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="gstin">{{ __('GSTIN (optional)') }}</label>
                        <input type="text" id="gstin" name="gstin" class="form-control mono"
                               value="{{ old('gstin') }}" maxlength="20">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="contact_person">{{ __('Contact person') }}</label>
                        <input type="text" id="contact_person" name="contact_person" class="form-control"
                               value="{{ old('contact_person') }}" required maxlength="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="mobile">{{ __('Mobile') }}</label>
                        <input type="tel" id="mobile" name="mobile" class="form-control mono" maxlength="10"
                               inputmode="numeric" value="{{ old('mobile') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="email">{{ __('Email') }}</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="address">{{ __('Address') }}</label>
                        <textarea id="address" name="address" class="form-control" rows="2" required maxlength="500">{{ old('address') }}</textarea>
                    </div>

                    <div class="col-12 js-geo">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label" for="state_id">{{ __('State') }}</label>
                                <select id="state_id" name="state_id" class="form-select js-state" required>
                                    <option value="">{{ __('Select state') }}</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}" @selected(old('state_id') == $state->id)>{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="district_id">{{ __('District') }}</label>
                                <select id="district_id" name="district_id" class="form-select js-district" disabled required>
                                    <option value="">{{ __('Select state first') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="city_id">{{ __('City') }}</label>
                                <select id="city_id" name="city_id" class="form-select js-city" disabled>
                                    <option value="">{{ __('Select district') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="pincode">{{ __('Pincode') }}</label>
                                <input type="text" id="pincode" name="pincode" class="form-control mono"
                                       maxlength="6" inputmode="numeric" value="{{ old('pincode') }}">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <span class="form-label d-block">{{ __('Brands you sell') }}</span>
                        <div class="row g-1">
                            @foreach ($brands as $brand)
                                <div class="col-6 col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="brands[]"
                                               value="{{ $brand->id }}" id="brand-{{ $brand->id }}"
                                               @checked(in_array($brand->id, (array) old('brands', [])))>
                                        <label class="form-check-label small" for="brand-{{ $brand->id }}">{{ $brand->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="about">{{ __('About your dealership') }}</label>
                        <textarea id="about" name="about" class="form-control" rows="3" maxlength="2000">{{ old('about') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-4">{{ __('Register my dealership') }}</button>
                <p class="form-text text-center mt-2">
                    {{ __('You will upload GST, PAN and authorisation documents from your dealer panel after registering.') }}
                </p>
            </form>
        </div>
    </div>
</div>
@endsection

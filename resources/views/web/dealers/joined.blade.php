@extends('layouts.app')

@section('title', __('Registration received | Krishi Junction'))

@section('content')
<div class="container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="kj-panel p-5">
                <span class="badge badge-warn mb-3">{{ __('Awaiting verification') }}</span>
                <h1 class="h4 mb-2">{{ __('Thanks — we have your registration') }}</h1>
                <p class="text-muted-2">
                    {{ __('Your dealer code is :code. Our team verifies documents within two working days; leads start once you are verified.', [
                        'code' => $dealer->code,
                    ]) }}
                </p>
                <div class="d-flex gap-2 justify-content-center flex-wrap mt-4">
                    <a href="{{ route('dealer.dashboard') }}" class="btn btn-primary">{{ __('Open my dealer panel') }}</a>
                    <a href="{{ route('home') }}" class="btn btn-outline-primary">{{ __('Back to home') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

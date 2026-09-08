@extends('layouts.app')

@section('title', __('Dealer panel — Krishi Junction'))

@section('content')
<div class="container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="kj-panel p-5">
                <h1 class="h5 mb-2">{{ __('No dealership linked to this account') }}</h1>
                <p class="text-muted-2 small">
                    {{ __('Register your dealership to get a panel, or ask your owner to add you as staff.') }}
                </p>
                <a href="{{ route('dealers.join') }}" class="btn btn-primary mt-2">{{ __('Register a dealership') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection

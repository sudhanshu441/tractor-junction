@extends('layouts.app')

@section('content')
<div class="container-xl py-5">
    <div class="kj-panel p-5 text-center mx-auto" style="max-width: 32rem;">
        @if ($found)
            <span class="badge badge-ok mb-2">{{ __('Unsubscribed') }}</span>
            <p class="fw-semibold mb-1">{{ __('You are off the list') }}</p>
            <p class="small text-muted-2 mb-3">{{ __('We will not email you again. Nothing else about your account changes.') }}</p>
        @else
            <span class="badge badge-muted mb-2">{{ __('Nothing to do') }}</span>
            <p class="fw-semibold mb-1">{{ __('That email is not on our list') }}</p>
            <p class="small text-muted-2 mb-3">{{ __('It may already have been removed.') }}</p>
        @endif
        <a href="{{ route('home') }}" class="btn btn-primary btn-sm">{{ __('Back to the website') }}</a>
    </div>
</div>
@endsection

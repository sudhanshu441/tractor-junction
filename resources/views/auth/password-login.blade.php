@extends('layouts.app')

@section('title', __('Staff login — Krishi Junction'))

@section('content')
<div class="container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="kj-panel p-4">
                <h1 class="h5 mb-1">{{ __('Staff & dealer login') }}</h1>
                <p class="text-muted-2 small mb-4">{{ __('Use the email address your administrator gave you.') }}</p>

                @if ($errors->any())
                    <div class="alert alert-danger small">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.password.store') }}">
                    @csrf
                    <label class="form-label" for="email">{{ __('Email') }}</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                           id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

                    <label class="form-label mt-3" for="password">{{ __('Password') }}</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                        <label class="form-check-label small" for="remember">{{ __('Keep me logged in') }}</label>
                    </div>

                    <button class="btn btn-primary w-100 mt-3" type="submit">{{ __('Log in') }}</button>
                </form>

                <hr class="my-4">
                <p class="small text-muted-2 mb-0">
                    <a href="{{ route('login') }}">{{ __('Log in with a mobile code instead') }}</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

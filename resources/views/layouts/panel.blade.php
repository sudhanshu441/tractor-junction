@extends('layouts.base')

@section('body')
    @include('partials.header')
    <main class="container-xl py-4">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="kj-panel p-3">
                    <div class="kj-label mb-2">@yield('panel_name')</div>
                    <ul class="nav flex-column small">
                        @yield('panel_nav')
                    </ul>
                </div>
            </div>
            <div class="col-lg-9">
                @if ($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                @yield('content')
            </div>
        </div>
    </main>
    @include('partials.footer')
@endsection

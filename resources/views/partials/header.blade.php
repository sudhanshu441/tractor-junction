<header class="kj-header sticky-top">
    <div class="container-xl">
        <nav class="navbar navbar-expand-lg py-2">
            <a class="navbar-brand" href="{{ route('home') }}">
                <img src="{{ asset('assets/brand/logo-horizontal.svg') }}" alt="{{ config('kj.brand.name') }}" class="kj-logo">
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#kjNav" aria-controls="kjNav" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="kjNav">
                <ul class="navbar-nav me-auto ms-lg-3">
                    @foreach ([
                        __('New Tractors') => '#', __('Used') => '#', __('Implements') => '#',
                        __('Compare') => '#', __('Dealers') => '#', __('Loan & EMI') => '#', __('News') => '#',
                    ] as $label => $url)
                        <li class="nav-item"><a class="nav-link" href="{{ $url }}">{{ $label }}</a></li>
                    @endforeach
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-muted mono">{{ strtoupper(app()->getLocale()) }}</span>
                    <a href="#" class="btn btn-deep btn-sm">{{ __('Sell your tractor') }}</a>

                    @auth
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ auth()->user()->initials }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if (auth()->user()->isStaff())
                                    <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">{{ __('Admin panel') }}</a></li>
                                @endif
                                @if (auth()->user()->isDealer())
                                    <li><a class="dropdown-item" href="{{ route('dealer.dashboard') }}">{{ __('Dealer panel') }}</a></li>
                                @endif
                                <li><a class="dropdown-item" href="{{ route('account.dashboard') }}">{{ __('My account') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit">{{ __('Log out') }}</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">{{ __('Login') }}</a>
                    @endauth
                </div>
            </div>
        </nav>
    </div>
</header>

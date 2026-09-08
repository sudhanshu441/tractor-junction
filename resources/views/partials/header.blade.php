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
                    {{-- Editor-managed; falls back to the built-in set if the menu is empty. --}}
                    @php
                        $headerMenu = app(\App\Domain\Content\Services\ContentService::class)->menu('header') ?: [
                            ['label' => 'New Tractors', 'url' => '/tractors', 'children' => []],
                            ['label' => 'Used', 'url' => '/used', 'children' => []],
                            ['label' => 'Dealers', 'url' => '/dealers', 'children' => []],
                            ['label' => 'Loan & EMI', 'url' => '/loan', 'children' => []],
                        ];
                    @endphp
                    @foreach ($headerMenu as $item)
                        @if (count($item['children']))
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="{{ \App\Support\Locale::urlFor(app()->getLocale(), $item['url']) }}"
                                   data-bs-toggle="dropdown" aria-expanded="false">{{ __($item['label']) }}</a>
                                <ul class="dropdown-menu">
                                    @foreach ($item['children'] as $child)
                                        <li><a class="dropdown-item" href="{{ \App\Support\Locale::urlFor(app()->getLocale(), $child['url']) }}">{{ __($child['label']) }}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link" href="{{ \App\Support\Locale::urlFor(app()->getLocale(), $item['url']) }}">{{ __($item['label']) }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"
                                aria-expanded="false" aria-label="{{ __('Change language') }}">
                            {{ \App\Support\Locale::supported()[app()->getLocale()] ?? 'English' }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @foreach (\App\Support\Locale::supported() as $code => $label)
                                <li>
                                    <a class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}"
                                       href="{{ \App\Support\Locale::urlFor($code) }}" hreflang="{{ $code }}"
                                       rel="alternate">{{ $label }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <a href="{{ route('sell.start') }}" class="btn btn-deep btn-sm">{{ __('Sell your tractor') }}</a>

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

<footer class="mt-5 border-top" style="background: var(--kj-surface);">
    <div class="container-xl py-4">
        <div class="row g-4">
            <div class="col-lg-4">
                <img src="{{ asset('assets/brand/logo-horizontal.svg') }}" alt="{{ config('kj.brand.name') }}" class="kj-logo mb-2">
                <p class="text-muted-2 small mb-0">{{ \App\Models\Setting::get('tagline') }}</p>
            </div>
            @foreach ([
                'Company' => [
                    'Become a dealer' => route('dealers.join'),
                    'Sell your tractor' => route('sell.start'),
                ],
                'Explore' => [
                    'New tractors' => route('catalog.tractors.index'),
                    'Used tractors' => route('used.index'),
                    'Implements' => route('catalog.implements.index'),
                    'Dealers' => route('dealers.index'),
                ],
                'Money matters' => [
                    'Tractor loans' => route('loan.hub'),
                    'EMI calculator' => route('emi.index'),
                    'Tractor insurance' => route('insurance.index'),
                ],
            ] as $heading => $links)
                <div class="col-6 col-lg-2">
                    <div class="kj-label mb-2">{{ __($heading) }}</div>
                    <ul class="list-unstyled small mb-0">
                        @foreach ($links as $link => $url)
                            <li class="mb-1"><a href="{{ $url }}" class="text-decoration-none text-muted-2">{{ __($link) }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <hr>
        <p class="small text-muted-2 mb-0">&copy; {{ date('Y') }} {{ config('kj.brand.legal_name') }}. {{ __('All rights reserved.') }}</p>
    </div>
</footer>

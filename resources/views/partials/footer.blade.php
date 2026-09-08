<footer class="mt-5 border-top" style="background: var(--kj-surface);">
    <div class="container-xl py-4">
        <div class="row g-4">
            <div class="col-lg-4">
                <img src="{{ asset('assets/brand/logo-horizontal.svg') }}" alt="{{ config('kj.brand.name') }}" class="kj-logo mb-2">
                <p class="text-muted-2 small mb-0">{{ \App\Models\Setting::get('tagline') }}</p>
            </div>
            @php
                $contentService = app(\App\Domain\Content\Services\ContentService::class);
                $footerColumns = array_filter([
                    __('Company') => $contentService->menu('footer_1'),
                    __('Explore') => $contentService->menu('footer_2'),
                ]);
            @endphp

            @foreach ($footerColumns as $heading => $links)
                <div class="col-6 col-lg-2">
                    <div class="kj-label mb-2">{{ $heading }}</div>
                    <ul class="list-unstyled small mb-0">
                        @foreach ($links as $link)
                            <li class="mb-1">
                                <a href="{{ \App\Support\Locale::urlFor(app()->getLocale(), $link['url']) }}"
                                   class="text-decoration-none text-muted-2">{{ __($link['label']) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="col-6 col-lg-2">
                <div class="kj-label mb-2">{{ __('Money matters') }}</div>
                <ul class="list-unstyled small mb-0">
                    @foreach ([
                        __('Tractor loans') => route('loan.hub'),
                        __('EMI calculator') => route('emi.index'),
                        __('Tractor insurance') => route('insurance.index'),
                    ] as $label => $url)
                        <li class="mb-1"><a href="{{ $url }}" class="text-decoration-none text-muted-2">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <p class="small text-muted-2 mb-0">
                &copy; {{ date('Y') }} {{ config('kj.brand.legal_name') }}. {{ __('All rights reserved.') }}
            </p>
            <ul class="list-unstyled d-flex gap-3 small mb-0">
                @foreach (\App\Models\Page::active()->where('show_in_footer', true)->orderBy('sort_order')->get(['title', 'slug']) as $page)
                    <li><a href="{{ route('pages.show', $page->slug) }}" class="text-decoration-none text-muted-2">{{ $page->title }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</footer>

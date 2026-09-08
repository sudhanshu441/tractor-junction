@extends('layouts.app')

@section('content')
<div class="container-xl py-4">
    <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
        <a href="{{ route('home') }}">{{ __('Home') }}</a>
        <span class="text-muted-2">/ {{ __('FAQ') }}</span>
    </nav>

    <h1 class="h4 mb-1">{{ __('Frequently asked questions') }}</h1>
    <p class="text-muted-2" style="max-width: 60ch;">
        {{ __('Buying, selling, loans and listings — the questions our team is asked most.') }}
    </p>

    <div class="d-flex gap-2 flex-wrap my-3">
        @foreach ($categories as $category)
            <a href="{{ route('faqs.index', ['category' => $category]) }}"
               class="btn btn-sm {{ $active === $category ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ ucfirst(str_replace('_', ' ', $category)) }}
            </a>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="accordion" id="faq-accordion">
                @forelse ($faqs as $i => $faq)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq-{{ $i }}"
                                    aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="faq-{{ $i }}">
                                {{ $faq['question'] }}
                            </button>
                        </h2>
                        <div id="faq-{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                             data-bs-parent="#faq-accordion">
                            <div class="accordion-body small">{!! nl2br(e($faq['answer'])) !!}</div>
                        </div>
                    </div>
                @empty
                    <p class="small text-muted-2">{{ __('No questions in this section yet.') }}</p>
                @endforelse
            </div>

            <div class="kj-panel p-4 mt-4">
                <h2 class="h6 mb-1">{{ __('Still stuck?') }}</h2>
                <p class="small text-muted-2 mb-2">{{ __('Our team answers on the phone in Hindi and English.') }}</p>
                <a href="{{ route('contact') }}" class="btn btn-primary btn-sm">{{ __('Contact us') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('schema')
@php $faqSchema = app(\App\Domain\Seo\Services\JsonLd::class)->faq($faqs); @endphp
@if ($faqSchema)
    <script type="application/ld+json">@json($faqSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)</script>
@endif
@endpush

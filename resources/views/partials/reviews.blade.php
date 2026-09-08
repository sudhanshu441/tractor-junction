{{--
    Owner reviews. Server-rendered so search engines index them; the write form
    and the helpful vote are the only AJAX here.

    Expects: $subject, $subjectType ('product'|'dealer'), $reviewSummary, $reviews, $reviewAspects
--}}
@php
    $canReview = auth()->check();
    $alreadyReviewed = $canReview && $subject->reviews()
        ->where('user_id', auth()->id())->exists();
@endphp

<section class="kj-panel p-4 mt-4" id="reviews">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <h2 class="h6 mb-0">{{ __('Owner reviews') }}</h2>
        @if (! $alreadyReviewed)
            <button type="button" class="btn btn-outline-primary btn-sm" id="write-review-toggle">
                {{ __('Write a review') }}
            </button>
        @endif
    </div>

    @if ($reviewSummary['total'] > 0)
        <div class="row g-4 align-items-center mb-4">
            <div class="col-6 col-md-3 text-center">
                <div class="display-6 mono">{{ number_format($reviewSummary['average'], 1) }}</div>
                <div class="text-warning" aria-hidden="true">
                    {{ str_repeat('★', (int) round($reviewSummary['average'])) }}{{ str_repeat('☆', 5 - (int) round($reviewSummary['average'])) }}
                </div>
                <div class="small text-muted-2">
                    {{ trans_choice('{1} :count review|[2,*] :count reviews', $reviewSummary['total'], ['count' => $reviewSummary['total']]) }}
                </div>
            </div>

            <div class="col-6 col-md-4">
                @foreach ($reviewSummary['distribution'] as $star => $count)
                    @php $pct = $reviewSummary['total'] ? round($count / $reviewSummary['total'] * 100) : 0; @endphp
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="small mono" style="width:1.5rem;">{{ $star }}★</span>
                        <div class="progress flex-grow-1" style="height:.4rem;">
                            <div class="progress-bar" style="width: {{ $pct }}%; background: var(--kj-green-700);"
                                 role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"
                                 aria-label="{{ __(':star star', ['star' => $star]) }}"></div>
                        </div>
                        <span class="small text-muted-2 mono" style="width:2rem;">{{ $count }}</span>
                    </div>
                @endforeach
            </div>

            @if (! empty($reviewSummary['aspects']))
                <div class="col-12 col-md-5">
                    <div class="row g-2">
                        @foreach ($reviewSummary['aspects'] as $aspect => $average)
                            <div class="col-6">
                                <div class="kj-stat">
                                    <div class="k">{{ ucfirst(str_replace('_', ' ', $aspect)) }}</div>
                                    <div class="v" style="font-size:1rem;">{{ number_format($average, 1) }}<span class="small text-muted-2">/5</span></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <p class="small text-muted-2">
            {{ __('No reviews yet. If you own one, yours would be the first — and the most useful.') }}
        </p>
    @endif

    <div id="review-form" class="border-top pt-3 mt-3" {{ $errors->any() ? '' : 'hidden' }}>
        @guest
            <p class="small mb-2">{{ __('Log in with your mobile number to write a review.') }}</p>
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">{{ __('Log in') }}</a>
        @endguest

        @auth
            @if ($alreadyReviewed)
                <p class="small text-muted-2 mb-0">{{ __('You have already reviewed this.') }}</p>
            @else
                <form id="review-form-fields">
                    <input type="hidden" name="subject_type" value="{{ $subjectType }}">
                    <input type="hidden" name="subject_id" value="{{ $subject->getKey() }}">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="review-rating">{{ __('Overall rating') }}</label>
                            <select id="review-rating" name="rating" class="form-select" required>
                                @foreach ([5, 4, 3, 2, 1] as $star)
                                    <option value="{{ $star }}">{{ $star }} ★</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($subjectType === 'product')
                            <div class="col-md-8">
                                <label class="form-label" for="review-ownership">{{ __('How long have you owned it?') }}</label>
                                <select id="review-ownership" name="ownership_duration" class="form-select">
                                    <option value="">{{ __('Prefer not to say') }}</option>
                                    @foreach (\App\Domain\Engagement\Services\ReviewService::OWNERSHIP_DURATIONS as $value => $label)
                                        <option value="{{ $value }}">{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label" for="review-title">{{ __('Headline') }}</label>
                            <input type="text" id="review-title" name="title" class="form-control" maxlength="150"
                                   placeholder="{{ __('e.g. Strong puller, thirsty on diesel') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="review-body">{{ __('Your review') }}</label>
                            <textarea id="review-body" name="body" class="form-control" rows="4"
                                      minlength="20" maxlength="3000" required
                                      placeholder="{{ __('How does it handle your land, your crop, your hours?') }}"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="review-pros">{{ __('What works well') }}</label>
                            <textarea id="review-pros" name="pros" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="review-cons">{{ __('What does not') }}</label>
                            <textarea id="review-cons" name="cons" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>

                        @foreach ($reviewAspects as $aspect)
                            <div class="col-6 col-md-3">
                                <label class="form-label" for="aspect-{{ $aspect }}">{{ ucfirst(str_replace('_', ' ', $aspect)) }}</label>
                                <select id="aspect-{{ $aspect }}" name="aspects[{{ $aspect }}]" class="form-select form-select-sm">
                                    <option value="">—</option>
                                    @foreach ([5, 4, 3, 2, 1] as $star)
                                        <option value="{{ $star }}">{{ $star }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <p class="small text-muted-2 mt-3 mb-2">
                        {{ __('Reviews are read by our team before they appear. Keep it about the machine, not the seller’s family.') }}
                    </p>
                    <button type="submit" class="btn btn-primary">{{ __('Submit review') }}</button>
                </form>
            @endif
        @endauth
    </div>

    @if ($reviews->isNotEmpty())
        <div class="mt-3">
            @foreach ($reviews as $review)
                <article class="py-3 border-top" id="review-{{ $review->id }}">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <b class="small">{{ $review->user?->name ?? __('Verified user') }}</b>
                            @if ($review->is_verified_owner)
                                <span class="badge badge-ok">{{ __('owner') }}</span>
                            @endif
                            <div class="small text-muted-2">
                                {{ $review->created_at->format('d M Y') }}
                                @if ($review->ownership_duration)
                                    · {{ __(\App\Domain\Engagement\Services\ReviewService::OWNERSHIP_DURATIONS[$review->ownership_duration] ?? $review->ownership_duration) }}
                                @endif
                            </div>
                        </div>
                        <span class="badge badge-muted">{{ $review->rating }} ★</span>
                    </div>

                    @if ($review->title)
                        <p class="fw-semibold mt-2 mb-1">{{ $review->title }}</p>
                    @endif
                    <p class="small mb-2">{{ $review->body }}</p>

                    @if ($review->pros || $review->cons)
                        <div class="row g-2 mb-2">
                            @if ($review->pros)
                                <div class="col-md-6"><span class="kj-label">{{ __('Pros') }}</span>
                                    <p class="small mb-0">{{ $review->pros }}</p></div>
                            @endif
                            @if ($review->cons)
                                <div class="col-md-6"><span class="kj-label">{{ __('Cons') }}</span>
                                    <p class="small mb-0">{{ $review->cons }}</p></div>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 js-vote-row" data-url="{{ route('ajax.reviews.vote', $review) }}">
                        <span class="small text-muted-2">{{ __('Was this helpful?') }}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-vote" data-helpful="1"
                                @guest disabled title="{{ __('Log in to vote') }}" @endguest>
                            {{ __('Yes') }} <span class="js-helpful mono">{{ $review->helpful_count }}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-vote" data-helpful="0"
                                @guest disabled title="{{ __('Log in to vote') }}" @endguest>
                            {{ __('No') }} <span class="js-unhelpful mono">{{ $review->unhelpful_count }}</span>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@push('scripts')
<script>
$(function () {
    $('#write-review-toggle').on('click', function () {
        var form = document.getElementById('review-form');
        form.hidden = !form.hidden;
        if (!form.hidden) { $('#review-body').trigger('focus'); }
    });

    $('#review-form-fields').on('submit', async function (event) {
        event.preventDefault();
        var form = this;
        var button = $(form).find('[type="submit"]').prop('disabled', true);

        var response = await KJ.request({
            url: '{{ route('ajax.reviews.store') }}',
            method: 'POST',
            data: $(form).serialize(),
        });

        if (response.status === 'ok') {
            $(form).replaceWith('<p class="small mb-0">' + response.message + '</p>');
            return;
        }

        button.prop('disabled', false);
        KJ.showErrors(form, response.errors);
        KJ.toast(response.message, 'danger');
    });

    $('.js-vote').on('click', async function () {
        var row = $(this).closest('.js-vote-row');
        row.find('.js-vote').prop('disabled', true);

        var response = await KJ.request({
            url: row.data('url'),
            method: 'POST',
            data: { helpful: $(this).data('helpful') },
        });

        if (response.status === 'ok') {
            row.find('.js-helpful').text(response.data.helpful);
            row.find('.js-unhelpful').text(response.data.unhelpful);
        } else {
            KJ.toast(response.message, 'danger');
        }

        row.find('.js-vote').prop('disabled', false);
    });
});
</script>
@endpush

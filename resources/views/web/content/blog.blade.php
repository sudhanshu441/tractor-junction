@extends('layouts.app')

@section('content')
<div class="container-xl py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-3">
                <a href="{{ route('home') }}">{{ __('Home') }}</a>
                <span class="text-muted-2">/</span>
                <a href="{{ route('blogs.index') }}">{{ __('News') }}</a>
                @if ($post->category)
                    <span class="text-muted-2">/</span>
                    <a href="{{ route('blogs.category', $post->category->slug) }}">{{ $post->category->name }}</a>
                @endif
            </nav>

            <article>
                @if ($post->category)
                    <span class="kj-eyebrow">{{ $post->category->name }}</span>
                @endif
                <h1 class="h3 mt-2 mb-2">{{ $post->title }}</h1>
                <p class="small text-muted-2">
                    {{ $post->author?->name ?? __('Krishi Junction') }} ·
                    {{ $post->published_at?->format('d M Y') }} ·
                    {{ trans_choice('{1} :count min read|[2,*] :count min read', $post->reading_minutes, ['count' => $post->reading_minutes]) }}
                </p>

                @if ($post->cover_image)
                    <img src="{{ asset('storage/'.$post->cover_image) }}" alt="{{ $post->title }}"
                         class="img-fluid rounded my-3">
                @endif

                @if ($post->excerpt)
                    <p class="lead" style="font-size: 1.05rem;">{{ $post->excerpt }}</p>
                @endif

                <div class="kj-prose">
                    {!! $post->content !!}
                </div>

                @if ($post->tags->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        @foreach ($post->tags as $tag)
                            <a href="{{ route('blogs.index', ['tag' => $tag->slug]) }}"
                               class="badge badge-muted text-decoration-none">#{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif
            </article>

            <section class="kj-panel p-4 mt-4" id="comments">
                <h2 class="h6 mb-3">
                    {{ trans_choice('{0} No comments yet|{1} One comment|[2,*] :count comments', $comments->count(), ['count' => $comments->count()]) }}
                </h2>

                @auth
                    <form id="comment-form" class="mb-4">
                        <label class="form-label" for="comment-body">{{ __('Leave a comment') }}</label>
                        <textarea id="comment-body" name="body" class="form-control mb-2" rows="3"
                                  minlength="5" maxlength="2000" required></textarea>
                        <p class="small text-muted-2">{{ __('Comments are read by our team before they appear.') }}</p>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Post comment') }}</button>
                    </form>
                @else
                    <p class="small mb-3">
                        <a href="{{ route('login') }}">{{ __('Log in') }}</a> {{ __('to leave a comment.') }}
                    </p>
                @endauth

                @foreach ($comments as $comment)
                    <div class="py-3 border-top">
                        <b class="small">{{ $comment->user?->name ?? __('Reader') }}</b>
                        <span class="small text-muted-2">· {{ $comment->created_at->diffForHumans() }}</span>
                        <p class="small mb-0 mt-1">{{ $comment->body }}</p>

                        @foreach ($comment->replies as $reply)
                            <div class="ps-4 mt-2 border-start">
                                <b class="small">{{ $reply->user?->name ?? __('Reader') }}</b>
                                <span class="small text-muted-2">· {{ $reply->created_at->diffForHumans() }}</span>
                                <p class="small mb-0 mt-1">{{ $reply->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </section>
        </div>

        <aside class="col-lg-4">
            @if (count($related))
                <div class="kj-panel p-3">
                    <h2 class="h6 mb-3">{{ __('Read next') }}</h2>
                    @foreach ($related as $other)
                        <div class="py-2 border-bottom">
                            <a href="{{ route('blogs.show', $other->slug) }}" class="small fw-semibold text-decoration-none">
                                {{ $other->title }}
                            </a>
                            <div class="small text-muted-2">{{ $other->published_at?->format('d M Y') }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="kj-panel p-3 mt-3">
                <h2 class="h6 mb-2">{{ __('Looking for a tractor?') }}</h2>
                <p class="small text-muted-2">{{ __('Compare on-road prices across every brand in your state.') }}</p>
                <a href="{{ route('catalog.tractors.index') }}" class="btn btn-primary btn-sm w-100">{{ __('Browse tractors') }}</a>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('schema')
@php
    $jsonLd = app(\App\Domain\Seo\Services\JsonLd::class);
    $articleSchema = $jsonLd->article($post);
    $breadcrumbSchema = $jsonLd->breadcrumbs([
        ['name' => __('Home'), 'url' => route('home')],
        ['name' => __('News'), 'url' => route('blogs.index')],
        ['name' => $post->title, 'url' => null],
    ]);
@endphp
<script type="application/ld+json">@json($articleSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)</script>
<script type="application/ld+json">@json($breadcrumbSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)</script>
@endpush

@push('scripts')
<script>
$(function () {
    $('#comment-form').on('submit', async function (event) {
        event.preventDefault();
        var form = this;
        var button = $(form).find('[type="submit"]').prop('disabled', true);

        var response = await KJ.request({
            url: '{{ route('ajax.blogs.comment', $post) }}',
            method: 'POST',
            data: { body: $('#comment-body').val() },
        });

        if (response.status === 'ok') {
            $(form).replaceWith('<p class="small mb-3"></p>');
            return $('#comments p.small').first().text(response.message);
        }

        button.prop('disabled', false);
        KJ.showErrors(form, response.errors);
        KJ.toast(response.message, 'danger');
    });
});
</script>
@endpush

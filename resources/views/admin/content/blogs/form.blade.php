@extends('layouts.admin')

@section('title', __('Post — Krishi Junction Admin'))
@section('page_title', $post->exists ? __('Edit post') : __('Write a post'))

@section('content')
<form method="POST" enctype="multipart/form-data"
      action="{{ $post->exists ? route('admin.blogs.update', $post) : route('admin.blogs.store') }}">
    @csrf
    @if ($post->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-body">
                <label class="form-label" for="title">{{ __('Title') }} <span style="color: var(--kj-danger);">*</span></label>
                <input type="text" id="title" name="title" class="form-control mb-3"
                       value="{{ old('title', $post->title) }}" maxlength="200" required>

                <label class="form-label" for="excerpt">{{ __('Excerpt') }}</label>
                <textarea id="excerpt" name="excerpt" class="form-control mb-1" rows="2"
                          maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea>
                <p class="small text-muted-2">{{ __('Shown on the listing page and used as the meta description. Left empty, the first lines of the article are used.') }}</p>

                <label class="form-label" for="content">{{ __('Article') }}</label>
                <textarea id="content" name="content" class="form-control mono" rows="20"
                          style="font-size:.8125rem;">{{ old('content', $post->content) }}</textarea>
                <p class="small text-muted-2 mb-0">
                    {{ __('HTML is allowed. Use h2 and h3 for headings — h1 is the title above.') }}
                </p>
            </div></div>

            <div class="card"><div class="card-body">
                <h2 class="h6 mb-3">{{ __('Search appearance') }}</h2>

                <label class="form-label" for="meta_title">{{ __('Meta title') }}</label>
                <input type="text" id="meta_title" name="meta_title" class="form-control mb-1"
                       value="{{ old('meta_title', $seo?->meta_title) }}" maxlength="200">
                <p class="small text-muted-2">{{ __('Leave empty to use the post title. Around 60 characters shows in full.') }}</p>

                <label class="form-label" for="meta_description">{{ __('Meta description') }}</label>
                <textarea id="meta_description" name="meta_description" class="form-control mb-1" rows="2"
                          maxlength="400">{{ old('meta_description', $seo?->meta_description) }}</textarea>
                <p class="small text-muted-2 mb-0">{{ __('Leave empty to use the excerpt.') }}</p>
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-body">
                <label class="form-label" for="status">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-select mb-3">
                    @foreach (['draft' => __('Draft'), 'scheduled' => __('Scheduled'), 'published' => __('Published'), 'archived' => __('Archived')] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $post->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <label class="form-label" for="published_at">{{ __('Publish date') }}</label>
                <input type="datetime-local" id="published_at" name="published_at" class="form-control mb-1"
                       value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                <p class="small text-muted-2">{{ __('A scheduled post appears on the website by itself when this time passes.') }}</p>

                <button type="submit" class="btn btn-primary w-100">{{ __('Save post') }}</button>

                @if ($post->exists)
                    <a href="{{ route('admin.blogs.index') }}" class="btn btn-link btn-sm w-100 mt-2">{{ __('Back to list') }}</a>
                @endif
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <label class="form-label" for="type">{{ __('Type') }}</label>
                <select id="type" name="type" class="form-select mb-3">
                    @foreach (['blog' => __('Blog'), 'news' => __('News'), 'guide' => __('Guide'), 'press' => __('Press release')] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $post->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <label class="form-label" for="blog_category_id">{{ __('Category') }}</label>
                <select id="blog_category_id" name="blog_category_id" class="form-select mb-3">
                    <option value="">{{ __('Uncategorised') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('blog_category_id', $post->blog_category_id) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <label class="form-label" for="tags">{{ __('Tags') }}</label>
                <input type="text" id="tags" name="tags" class="form-control mb-1"
                       value="{{ old('tags', $post->exists ? $post->tags->pluck('name')->implode(', ') : '') }}"
                       placeholder="{{ __('mahindra, subsidy, 2026') }}">
                <p class="small text-muted-2 mb-3">{{ __('Comma separated. New tags are created as you type them.') }}</p>

                <label class="form-label" for="slug">{{ __('URL') }}</label>
                <input type="text" id="slug" name="slug" class="form-control mb-1"
                       value="{{ old('slug', $post->slug) }}" maxlength="220">
                <p class="small text-muted-2 mb-0">{{ __('Leave empty to build it from the title. Changing it on a published post breaks existing links — add a redirect.') }}</p>
            </div></div>

            <div class="card"><div class="card-body">
                <label class="form-label" for="cover_image">{{ __('Cover image') }}</label>
                <input type="file" id="cover_image" name="cover_image" class="form-control" accept="image/*">
                @if ($post->cover_image)
                    <img src="{{ asset('storage/'.$post->cover_image) }}" alt="" class="img-fluid rounded mt-2">
                @endif

                <div class="form-check mt-3">
                    <input type="hidden" name="is_featured" value="0">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                           @checked(old('is_featured', $post->is_featured))>
                    <label class="form-check-label" for="is_featured">{{ __('Feature at the top of the news page') }}</label>
                </div>
            </div></div>
        </div>
    </div>
</form>

@if ($post->exists)
    <form method="POST" action="{{ route('admin.blogs.destroy', $post) }}" class="mt-3"
          onsubmit="return confirm('{{ __('Delete this post?') }}')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-secondary"
                style="color: var(--kj-danger); border-color: var(--kj-danger);">{{ __('Delete post') }}</button>
    </form>
@endif
@endsection

@extends('layouts.admin')

@section('title', __('SEO — Krishi Junction Admin'))
@section('page_title', __('Redirects & 404s'))

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        __('Active redirects') => [$counts['redirects'], false],
        __('Broken addresses') => [$counts['not_found'], $counts['not_found'] > 0],
        __('Total 404 hits') => [$counts['hits'], false],
    ] as $label => [$value, $alert])
        <div class="col-12 col-md-4">
            <div class="kj-stat {{ $alert ? 'is-alert' : '' }}">
                <div class="k">{{ $label }}</div><div class="v">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 mb-3">{{ __('Add a redirect') }}</h2>
            <form method="POST" action="{{ route('admin.seo.redirects.store') }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label" for="from_url">{{ __('Old address') }}</label>
                        <input type="text" id="from_url" name="from_url" class="form-control form-control-sm mono"
                               placeholder="/old-page" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="to_url">{{ __('New address') }}</label>
                        <input type="text" id="to_url" name="to_url" class="form-control form-control-sm mono"
                               placeholder="/tractors/mahindra" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="status_code">{{ __('Type') }}</label>
                        <select id="status_code" name="status_code" class="form-select form-select-sm">
                            <option value="301">301</option>
                            <option value="302">302</option>
                        </select>
                    </div>
                </div>
                <p class="small text-muted-2 mt-2 mb-2">
                    {{ __('301 is permanent and passes ranking; 302 is temporary. If the new address itself redirects, we point straight at the final one so crawlers do not follow a chain.') }}
                </p>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Save redirect') }}</button>
            </form>
        </div></div>

        <div class="card"><div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>{{ __('From') }}</th><th>{{ __('To') }}</th>
                            <th>{{ __('Type') }}</th><th class="num">{{ __('Hits') }}</th><th></th></tr>
                    </thead>
                    <tbody>
                    @forelse ($redirects as $redirect)
                        <tr>
                            <td class="mono small">{{ $redirect->from_url }}</td>
                            <td class="mono small">{{ Str::limit($redirect->to_url, 40) }}</td>
                            <td><span class="badge badge-muted">{{ $redirect->status_code }}</span></td>
                            <td class="num mono small">{{ number_format($redirect->hit_count) }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.seo.redirects.destroy', $redirect) }}"
                                      onsubmit="return confirm('{{ __('Remove this redirect?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-secondary"
                                            style="color: var(--kj-danger); border-color: var(--kj-danger);">{{ __('Remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted-2 py-4">{{ __('No redirects yet.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div></div>

        <div class="mt-3">{{ $redirects->links() }}</div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">{{ __('Most-hit broken addresses') }}</h2>
                @if ($notFound->isNotEmpty())
                    <form method="POST" action="{{ route('admin.seo.notfound.clear') }}"
                          onsubmit="return confirm('{{ __('Clear the whole 404 log?') }}')">
                        @csrf @method('DELETE')
                        <button class="btn btn-link btn-sm p-0">{{ __('Clear log') }}</button>
                    </form>
                @endif
            </div>
            <p class="small text-muted-2">
                {{ __('These are the addresses people and crawlers are actually asking for. Each one is a redirect waiting to be written.') }}
            </p>

            @forelse ($notFound as $log)
                <form method="POST" action="{{ route('admin.seo.notfound.resolve', $log) }}"
                      class="py-2 border-bottom">
                    @csrf
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <span class="mono small text-break">{{ $log->url }}</span>
                        <span class="badge badge-warn">{{ $log->hit_count }}</span>
                    </div>
                    @if ($log->referer)
                        <div class="small text-muted-2">{{ __('from') }} {{ Str::limit($log->referer, 50) }}</div>
                    @endif
                    <div class="input-group input-group-sm mt-1">
                        <input type="text" name="to_url" class="form-control mono"
                               placeholder="{{ __('redirect to…') }}" required>
                        <button class="btn btn-outline-primary" type="submit">{{ __('Fix') }}</button>
                    </div>
                </form>
            @empty
                <p class="small text-muted-2 mb-0">{{ __('Nothing has 404d. That is the number you want.') }}</p>
            @endforelse
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h6 mb-2">{{ __('Sitemaps') }}</h2>
            <p class="small text-muted-2">
                {{ __('Rebuilt nightly. Rebuild by hand after a bulk import or a URL change.') }}
            </p>
            <button type="button" class="btn btn-outline-primary btn-sm" id="rebuild-sitemap">{{ __('Rebuild now') }}</button>
            <a href="{{ route('sitemap.index') }}" target="_blank" rel="noopener"
               class="btn btn-link btn-sm">{{ __('View sitemap') }}</a>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#rebuild-sitemap').on('click', async function () {
        var button = $(this).prop('disabled', true).text('{{ __('Building…') }}');
        var response = await KJ.request({ url: '{{ route('admin.seo.sitemap') }}', method: 'POST' });

        button.prop('disabled', false).text('{{ __('Rebuild now') }}');
        KJ.toast(response.message, response.status === 'ok' ? 'success' : 'danger');
    });
});
</script>
@endpush

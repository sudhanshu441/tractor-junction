@extends('layouts.admin')

@section('title', $config['label'].' — Krishi Junction Admin')
@section('page_title', __(':label', ['label' => Str::plural($config['label'])]))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <p class="small text-muted-2 mb-0">
        {{ trans_choice('{0} Nothing here yet|{1} One entry|[2,*] :count entries', $rows->total(), ['count' => $rows->total()]) }}
    </p>
    <a href="{{ route('admin.content.create', $resource) }}" class="btn btn-primary btn-sm">
        {{ __('Add :label', ['label' => strtolower($config['label'])]) }}
    </a>
</div>

<div class="card"><div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    @foreach ($config['columns'] as $column)
                        <th>{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($config['columns'] as $column)
                        <td class="{{ str_contains($column, 'count') || $column === 'sort_order' ? 'num mono small' : 'small' }}">
                            @if (is_bool($row->{$column}))
                                <span class="badge {{ $row->{$column} ? 'badge-ok' : 'badge-muted' }}">
                                    {{ $row->{$column} ? __('yes') : __('no') }}
                                </span>
                            @else
                                {{ Str::limit((string) $row->{$column}, 80) }}
                            @endif
                        </td>
                    @endforeach
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.content.edit', [$resource, $row->id]) }}"
                           class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('admin.content.destroy', [$resource, $row->id]) }}"
                              class="d-inline js-confirm">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                    style="color: var(--kj-danger); border-color: var(--kj-danger);">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($config['columns']) + 1 }}" class="text-center text-muted-2 py-4">
                        {{ __('Nothing here yet.') }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>

<div class="mt-3">{{ $rows->links() }}</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.js-confirm').on('submit', function () {
        return window.confirm('{{ __('Delete this permanently?') }}');
    });
});
</script>
@endpush

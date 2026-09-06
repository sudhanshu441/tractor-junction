@extends('layouts.admin')

@section('title', __('Edit role').' — '.$role->name)
@section('page_title', __('Permissions for :role', ['role' => $role->name]))

@section('content')
@php $locked = $role->name === 'super-admin'; @endphp

@if ($locked)
    <div class="alert alert-secondary small">
        {{ __('The super-admin role always holds every permission and cannot be edited.') }}
    </div>
@endif

<form method="POST" action="{{ route('admin.roles.update', $role) }}">
    @csrf
    @method('PUT')

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <p class="text-muted-2 small mb-0">
            {{ __(':count permissions across :groups modules.', ['count' => $groups->flatten()->count(), 'groups' => $groups->count()]) }}
        </p>
        @unless ($locked)
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="check-all">{{ __('Select all') }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="uncheck-all">{{ __('Clear all') }}</button>
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Save permissions') }}</button>
            </div>
        @endunless
    </div>

    <div class="row g-3">
        @foreach ($groups as $module => $permissions)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="h6 mb-0 text-capitalize">{{ str_replace('_', ' ', $module) }}</h3>
                            <span class="badge badge-muted mono">{{ $permissions->count() }}</span>
                        </div>

                        @foreach ($permissions as $permission)
                            @php $action = explode('.', $permission->name)[1] ?? $permission->name; @endphp
                            <div class="form-check">
                                <input class="form-check-input js-perm" type="checkbox"
                                       name="permissions[]" value="{{ $permission->name }}"
                                       id="perm-{{ $permission->id }}"
                                       @checked($locked || in_array($permission->name, $assigned, true))
                                       @disabled($locked)>
                                <label class="form-check-label small" for="perm-{{ $permission->id }}">
                                    {{ str_replace('_', ' ', $action) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
    $('#check-all').on('click', function () { $('.js-perm:not(:disabled)').prop('checked', true); });
    $('#uncheck-all').on('click', function () { $('.js-perm:not(:disabled)').prop('checked', false); });
});
</script>
@endpush

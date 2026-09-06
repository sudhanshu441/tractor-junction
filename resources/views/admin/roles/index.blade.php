@extends('layouts.admin')

@section('title', __('Roles & permissions — Krishi Junction Admin'))
@section('page_title', __('Roles & permissions'))

@section('content')
<p class="text-muted-2 small">{{ __('Ten roles across the four panels. Permissions are :module.:action pairs enforced on every route.') }}</p>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Role') }}</th>
                        <th class="num">{{ __('Permissions') }}</th>
                        <th class="num">{{ __('Users') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td>
                            <b>{{ $role->name }}</b>
                            @if ($role->name === 'super-admin')
                                <span class="badge badge-ok ms-1">{{ __('All access') }}</span>
                            @endif
                        </td>
                        <td class="num mono">{{ $role->permissions_count }}</td>
                        <td class="num mono">{{ $role->users_count }}</td>
                        <td class="text-end">
                            @can('roles.view')
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                                    {{ $role->name === 'super-admin' ? __('View') : __('Edit permissions') }}
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

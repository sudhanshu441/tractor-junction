@extends('layouts.admin')

@section('title', ($user->exists ? __('Edit staff user') : __('Add staff user')).' — Krishi Junction Admin')
@section('page_title', $user->exists ? __('Edit staff user') : __('Add staff user'))

@section('content')
<form method="POST" action="{{ $user->exists ? route('admin.staff.update', $user) : route('admin.staff.store') }}">
    @csrf
    @if ($user->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Details') }}</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">{{ __('Full name') }}</label>
                            <input type="text" id="name" name="name" maxlength="100" required
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="mobile">{{ __('Mobile') }}</label>
                            <input type="tel" id="mobile" name="mobile" maxlength="10" inputmode="numeric" required
                                   class="form-control mono @error('mobile') is-invalid @enderror"
                                   value="{{ old('mobile', $user->mobile) }}">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">{{ __('Email') }}</label>
                            <input type="email" id="email" name="email" required
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="role">{{ __('Role') }}</label>
                            <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="">{{ __('Select a role') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}"
                                        @selected(old('role', $user->roles->first()?->name) === $role->name)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="password">
                                {{ $user->exists ? __('New password (leave blank to keep)') : __('Password') }}
                            </label>
                            <input type="password" id="password" name="password" autocomplete="new-password"
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">{{ __('Minimum 10 characters.') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">{{ __('Confirm password') }}</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Access') }}</h2>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                               name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                        <label class="form-check-label" for="is_active">{{ __('Account active') }}</label>
                    </div>
                    <p class="form-text">{{ __('A blocked user cannot log in to any panel.') }}</p>

                    <hr>
                    <button class="btn btn-primary w-100" type="submit">
                        {{ $user->exists ? __('Save changes') : __('Create staff user') }}
                    </button>
                    <a href="{{ route('admin.staff.index') }}" class="btn btn-link w-100 mt-1">{{ __('Cancel') }}</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

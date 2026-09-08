@extends('layouts.admin')

@section('title', $config['label'].' — Krishi Junction Admin')
@section('page_title', $row->exists ? __('Edit :label', ['label' => strtolower($config['label'])]) : __('New :label', ['label' => strtolower($config['label'])]))

@section('content')
<form method="POST" enctype="multipart/form-data"
      action="{{ $row->exists ? route('admin.content.update', [$resource, $row->id]) : route('admin.content.store', $resource) }}">
    @csrf
    @if ($row->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card"><div class="card-body">
                <div class="row g-3">
                    @foreach ($config['rules'] as $field => $rules)
                        @php
                            $required = in_array('required', $rules, true);
                            $isBool = in_array('boolean', $rules, true);
                            $isFile = in_array('image', $rules, true);
                            $isLong = $field === 'answer' || $field === 'message' || $field === 'description';
                            $enum = collect($rules)->first(fn ($r) => is_string($r) && str_starts_with($r, 'in:'));
                            $isDate = in_array('date', $rules, true);
                            $isNumber = in_array('integer', $rules, true) || in_array('numeric', $rules, true);
                            $wide = $isLong || in_array($field, ['question', 'title', 'cta_url'], true);
                        @endphp

                        <div class="col-12 {{ $wide ? '' : 'col-md-6' }}">
                            @if ($isBool)
                                <div class="form-check mt-4">
                                    <input type="hidden" name="{{ $field }}" value="0">
                                    <input class="form-check-input" type="checkbox" id="f-{{ $field }}"
                                           name="{{ $field }}" value="1"
                                           @checked(old($field, $row->{$field} ?? ($field === 'is_active')))>
                                    <label class="form-check-label" for="f-{{ $field }}">
                                        {{ ucfirst(str_replace('_', ' ', $field)) }}
                                    </label>
                                </div>
                            @else
                                <label class="form-label" for="f-{{ $field }}">
                                    {{ ucfirst(str_replace('_', ' ', $field)) }}
                                    @if ($required)<span style="color: var(--kj-danger);">*</span>@endif
                                </label>

                                @if ($enum)
                                    <select class="form-select" id="f-{{ $field }}" name="{{ $field }}" @required($required)>
                                        @unless ($required)<option value="">—</option>@endunless
                                        @foreach (explode(',', substr($enum, 3)) as $option)
                                            <option value="{{ $option }}" @selected(old($field, $row->{$field}) === $option)>
                                                {{ ucfirst(str_replace('_', ' ', $option)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($field === 'product_id')
                                    <select class="form-select" id="f-{{ $field }}" name="{{ $field }}">
                                        <option value="">{{ __('Not about one model') }}</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected(old($field, $row->product_id) == $product->id)>
                                                {{ $product->brand?->name }} {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($isFile)
                                    <input type="file" class="form-control" id="f-{{ $field }}" name="{{ $field }}" accept="image/*">
                                    @if ($row->{$field})
                                        <img src="{{ asset('storage/'.$row->{$field}) }}" alt="" class="img-fluid rounded mt-2" style="max-height: 120px;">
                                        <p class="small text-muted-2 mb-0">{{ __('Leave empty to keep the current image.') }}</p>
                                    @endif
                                @elseif ($isLong)
                                    <textarea class="form-control" id="f-{{ $field }}" name="{{ $field }}" rows="4"
                                              @required($required)>{{ old($field, $row->{$field}) }}</textarea>
                                @else
                                    <input type="{{ $isDate ? 'date' : ($isNumber ? 'number' : 'text') }}"
                                           class="form-control" id="f-{{ $field }}" name="{{ $field }}"
                                           value="{{ old($field, $isDate ? $row->{$field}?->format('Y-m-d') : $row->{$field}) }}"
                                           @required($required)>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <button type="submit" class="btn btn-primary w-100">{{ __('Save') }}</button>
                <a href="{{ route('admin.content.index', $resource) }}"
                   class="btn btn-link btn-sm w-100 mt-2">{{ __('Back to list') }}</a>
            </div></div>
        </div>
    </div>
</form>
@endsection

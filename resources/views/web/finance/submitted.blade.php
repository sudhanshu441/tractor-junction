@extends('layouts.app')

@section('title', __('Loan application submitted | Krishi Junction'))

@section('content')
<div class="container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="kj-panel p-5">
                <span class="badge badge-ok mb-3">{{ __('Submitted') }}</span>
                <h1 class="h4 mb-2">{{ __('Your application is with our finance team') }}</h1>
                <p class="text-muted-2">
                    {{ __('Reference :ref. We check your documents, then send it to the lenders that fit your profile.', [
                        'ref' => $application->reference_no,
                    ]) }}
                </p>

                <div class="row g-2 my-4 text-start">
                    @foreach ([
                        __('Loan amount') => '₹'.number_format((float) $application->loan_amount),
                        __('Indicative EMI') => '₹'.number_format((float) $application->calculated_emi),
                        __('Tenure') => $application->tenure_months.' '.__('months'),
                        __('Status') => ucfirst(str_replace('_', ' ', $application->status)),
                    ] as $label => $value)
                        <div class="col-6">
                            <div class="kj-stat"><div class="k">{{ $label }}</div>
                            <div class="v" style="font-size:1rem;">{{ $value }}</div></div>
                        </div>
                    @endforeach
                </div>

                @if ($missing)
                    <div class="alert alert-warning small text-start">
                        <b>{{ __('Still needed:') }}</b>
                        {{ implode(', ', array_map(fn ($d) => str_replace('_', ' ', $d), $missing)) }}.
                        {{ __('Your application waits until these arrive.') }}
                    </div>
                @endif

                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <a href="{{ route('account.dashboard') }}" class="btn btn-primary">{{ __('Track it in my account') }}</a>
                    <a href="{{ route('emi.index') }}" class="btn btn-outline-primary">{{ __('Back to the calculator') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

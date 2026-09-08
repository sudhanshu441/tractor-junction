@extends('layouts.app')

@section('title', __('Tractor Loans — Rates, EMI & Apply Online | Krishi Junction'))
@section('meta_description', __('Compare tractor loan rates, calculate your EMI and apply online. Repayment options that match the harvest, not just the calendar.'))

@section('content')
<section class="kj-tint border-bottom">
    <div class="container-xl py-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="kj-eyebrow">{{ __('Finance') }}</div>
                <h1 class="display-6 mt-2 mb-2">{{ __('Tractor loans, without the guesswork.') }}</h1>
                <p class="text-muted-2 mb-4" style="max-width: 56ch;">
                    {{ __('Work out what you would pay, check whether you qualify, and apply once — we take it to the lenders that fit.') }}
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('emi.index') }}" class="btn btn-primary">{{ __('Calculate my EMI') }}</a>
                    <a href="{{ route('loan.apply') }}" class="btn btn-deep">{{ __('Apply for a loan') }}</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="kj-panel p-4">
                    <div class="kj-label mb-2">{{ __('How it works') }}</div>
                    <ol class="small mb-0 ps-3">
                        <li class="mb-2">{{ __('Tell us the machine and what you can pay upfront.') }}</li>
                        <li class="mb-2">{{ __('Upload your documents — stored privately, never shared with dealers.') }}</li>
                        <li class="mb-2">{{ __('We match you to lenders whose criteria you actually meet.') }}</li>
                        <li>{{ __('You track every stage from your account.') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container-xl py-5">
    <h2 class="h5 mb-3">{{ __('Lenders we work with') }}</h2>

    <div class="table-responsive kj-panel">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Lender') }}</th><th>{{ __('Type') }}</th>
                    <th class="num">{{ __('Interest') }}</th><th class="num">{{ __('Max tenure') }}</th>
                    <th class="num">{{ __('Processing fee') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lenders as $lender)
                    <tr>
                        <td><b>{{ $lender->name }}</b></td>
                        <td class="small text-muted-2">{{ strtoupper($lender->lender_type) }}</td>
                        <td class="num mono">{{ $lender->interest_min }}–{{ $lender->interest_max }}%</td>
                        <td class="num mono">{{ $lender->tenure_max_months }} {{ __('mo') }}</td>
                        <td class="num mono">{{ $lender->processing_fee_percent ? $lender->processing_fee_percent.'%' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center small text-muted-2 py-4">{{ __('Lender panel is being set up.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="small text-muted-2 mt-3">
        {{ __('Rates are indicative and set by the lender, not by Krishi Junction. Your actual rate depends on their assessment.') }}
    </p>
</section>
@endsection

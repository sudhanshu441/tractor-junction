@extends('layouts.app')

@section('title', $product
    ? __(':name loan EMI calculator | Krishi Junction', ['name' => $product->full_name])
    : __('Tractor EMI Calculator — Calculate Your Loan EMI | Krishi Junction'))
@section('meta_description', __('Work out your tractor loan EMI in three steps. Monthly, quarterly, half-yearly or yearly repayment, with a full amortisation schedule.'))

@section('content')
<div class="container-xl py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('loan.hub') }}">{{ __('Loan & EMI') }}</a></li>
            <li class="breadcrumb-item active">{{ __('EMI calculator') }}</li>
        </ol>
    </nav>

    <h1 class="h4 mb-1">
        {{ $product ? __(':name — loan EMI calculator', ['name' => $product->full_name]) : __('Tractor loan EMI calculator') }}
    </h1>
    <p class="text-muted-2 small mb-4">
        {{ __('Farm income arrives at harvest, so quarterly, half-yearly and yearly repayment are all here — not just monthly.') }}
    </p>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="kj-panel p-4">
                <input type="hidden" id="product_id" value="{{ $product?->id }}">

                <label class="form-label" for="price">{{ __('Machinery price') }}</label>
                <div class="input-group mb-3">
                    <span class="input-group-text">₹</span>
                    <input type="number" id="price" class="form-control mono js-emi-input"
                           value="{{ (int) $inputs['price'] }}" min="10000" step="1000">
                </div>

                <label class="form-label" for="down_payment">{{ __('Down payment') }}</label>
                <div class="input-group mb-1">
                    <span class="input-group-text">₹</span>
                    <input type="number" id="down_payment" class="form-control mono js-emi-input"
                           value="{{ (int) $inputs['downPayment'] }}" min="0" step="1000">
                </div>
                <input type="range" class="form-range mb-3" id="down_range" min="0" max="100" step="5"
                       value="{{ (int) round($inputs['downPayment'] / max($inputs['price'], 1) * 100) }}"
                       aria-label="{{ __('Down payment percentage') }}">

                <label class="form-label" for="rate">{{ __('Interest rate (% per year)') }}</label>
                <input type="number" id="rate" class="form-control mono mb-3 js-emi-input"
                       value="{{ $inputs['rate'] }}" min="0" max="36" step="0.1">

                <span class="form-label d-block">{{ __('Tenure') }}</span>
                <div class="d-flex gap-2 flex-wrap mb-3" id="tenure-group">
                    @foreach ([12, 24, 36, 48, 60, 72, 84] as $months)
                        <button type="button"
                                class="btn btn-sm {{ $inputs['tenure'] === $months ? 'btn-primary' : 'btn-outline-primary' }} js-tenure"
                                data-value="{{ $months }}">{{ $months }} {{ __('mo') }}</button>
                    @endforeach
                </div>
                <input type="hidden" id="tenure" value="{{ $inputs['tenure'] }}">

                <span class="form-label d-block">{{ __('Repayment frequency') }}</span>
                <div class="d-flex gap-2 flex-wrap" id="frequency-group">
                    @foreach ([
                        'monthly' => __('Monthly'), 'quarterly' => __('Quarterly'),
                        'half_yearly' => __('Half-yearly'), 'yearly' => __('Yearly'),
                    ] as $value => $label)
                        <button type="button"
                                class="btn btn-sm {{ $inputs['frequency'] === $value ? 'btn-primary' : 'btn-outline-primary' }} js-frequency"
                                data-value="{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>
                <input type="hidden" id="frequency" value="{{ $inputs['frequency'] }}">
            </div>
        </div>

        <div class="col-lg-5">
            <div class="kj-panel p-4 text-center">
                <div class="kj-eyebrow">{{ __('Your instalment') }}</div>
                <div class="h2 mb-0 mono" id="emi-value">₹{{ number_format($result['emi']) }}</div>
                <div class="small text-muted-2" id="emi-caption">
                    {{ trans_choice('over :count instalment|over :count instalments', $result['instalments'], ['count' => $result['instalments']]) }}
                </div>

                <div class="row g-2 mt-3">
                    @foreach ([
                        __('Loan amount') => ['loan-amount', $result['loan_amount']],
                        __('Total interest') => ['total-interest', $result['total_interest']],
                        __('Total payable') => ['total-payable', $result['total_payable']],
                        __('Down payment') => ['down-shown', $inputs['downPayment']],
                    ] as $label => [$id, $value])
                        <div class="col-6">
                            <div class="kj-stat">
                                <div class="k">{{ $label }}</div>
                                <div class="v" style="font-size:1rem;" id="{{ $id }}">₹{{ number_format($value) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <a href="{{ route('loan.apply', array_filter(['product' => $product?->id])) }}"
                   class="btn btn-primary w-100 mt-3">{{ __('Apply for this loan') }}</a>
                <button type="button" class="btn btn-outline-primary w-100 mt-2" id="share-emi">
                    {{ __('Copy shareable link') }}
                </button>
            </div>

            <div class="kj-panel p-3 mt-3">
                <h2 class="h6 mb-2">{{ __('Repayment schedule') }}</h2>
                <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                    <table class="table table-sm mb-0" id="schedule-table">
                        <thead>
                            <tr><th>{{ __('Year') }}</th><th class="num">{{ __('Principal') }}</th>
                                <th class="num">{{ __('Interest') }}</th><th class="num">{{ __('Balance') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($schedule as $row)
                                <tr>
                                    <td class="mono">{{ $row['year'] }}</td>
                                    <td class="num mono">{{ number_format($row['principal']) }}</td>
                                    <td class="num mono">{{ number_format($row['interest']) }}</td>
                                    <td class="num mono">{{ number_format($row['balance']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($lenders->isNotEmpty())
        <section class="mt-5">
            <h2 class="h5 mb-3">{{ __('Lenders we work with') }}</h2>
            <div class="row g-3">
                @foreach ($lenders as $lender)
                    <div class="col-6 col-md-3">
                        <div class="card h-100"><div class="card-body">
                            <b class="small">{{ $lender->name }}</b>
                            <div class="small text-muted-2 mono">
                                {{ $lender->interest_min }}–{{ $lender->interest_max }}%
                            </div>
                            <div class="small text-muted-2">
                                {{ __('up to :months months', ['months' => $lender->tenure_max_months]) }}
                            </div>
                        </div></div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/finance.js') }}?v={{ config('app.asset_version', '1') }}"></script>
<script>$(function () { KJ.initEmi({ url: '{{ route('ajax.emi.calculate') }}' }); });</script>
@endpush

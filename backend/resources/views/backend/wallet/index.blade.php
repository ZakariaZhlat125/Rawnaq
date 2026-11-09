@extends('backend.layouts.app')

@php
    $t = fn ($k) => __('wallet_ledger.admin.'.$k);
    $money = fn ($v) => number_format((float) $v, 2);
@endphp

@section('title') {{ $t('title') }} @endsection

@section('content')
@include('backend.wallet._nav')

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <small class="text-muted">{{ $t('liability') }}</small>
            <h3 class="mb-1">{{ $money($liability->total) }} <small>SAR</small></h3>
            <small>{{ $t('cash') }}: {{ $money($liability->cash) }} · {{ $t('bonus') }}: {{ $money($liability->bonus) }}</small>
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="card h-100"><div class="card-body">
            <form class="row g-2 align-items-end mb-3" method="get">
                <div class="col-auto"><label class="form-label mb-0">{{ $t('from') }}</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm"></div>
                <div class="col-auto"><label class="form-label mb-0">{{ $t('to') }}</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm"></div>
                <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">{{ $t('filter') }}</button></div>
            </form>
            <div class="row text-center g-2">
                <div class="col"><small class="text-muted d-block">{{ $t('topups') }}</small><b>{{ $money($stats['topups']) }}</b></div>
                <div class="col"><small class="text-muted d-block">{{ $t('spent') }}</small><b>{{ $money($stats['spent']) }}</b></div>
                <div class="col"><small class="text-muted d-block">{{ $t('refunds') }}</small><b>{{ $money($stats['refunds']) }}</b></div>
                <div class="col"><small class="text-muted d-block">{{ $t('bonus_granted') }}</small><b>{{ $money($stats['bonus_granted']) }}</b></div>
                <div class="col"><small class="text-muted d-block">{{ $t('expired') }}</small><b>{{ $money($stats['expired']) }}</b></div>
                <div class="col"><small class="text-muted d-block">{{ $t('withdrawn') }}</small><b>{{ $money($stats['withdrawn']) }}</b></div>
            </div>
            <div class="mt-3 small">
                {{ $t('pending_withdrawals') }}: <b>{{ $stats['pending_withdrawals'] }}</b>
                @if ($stats['drift'] > 0)
                    <span class="text-danger ms-3">{{ $t('drift') }}: {{ $stats['drift'] }}</span>
                @endif
            </div>
        </div></div>
    </div>
</div>

@can('adjust_wallet_balance')
<div class="card">
    <div class="card-body">
        <h5>{{ $t('adjust') }}</h5>
        <form method="get" class="row g-2 mb-3">
            <input type="hidden" name="from" value="{{ $from->toDateString() }}">
            <input type="hidden" name="to" value="{{ $to->toDateString() }}">
            <div class="col-md-6"><input name="customer" value="{{ request('customer') }}" class="form-control" placeholder="{{ $t('search_customer') }}" required></div>
            <div class="col-auto"><button class="btn btn-outline-primary">{{ $t('search') }}</button></div>
        </form>

        @if (request()->filled('customer') && ! $customer)
            <div class="alert alert-warning">{{ $t('customer_not_found') }}</div>
        @endif

        @if ($customer)
            <div class="mb-3">
                <b>#{{ $customer->id }} — {{ trim($customer->first_name.' '.$customer->last_name) }}</b> ({{ $customer->mobile }})
                <div>
                    {{ $t('cash') }}: <b>{{ $money($customerWallet['cash_balance']) }}</b> ·
                    {{ $t('bonus') }}: <b>{{ $money($customerWallet['bonus_balance']) }}</b> ·
                    <a href="{{ route('wallet.history', $customer->id) }}">{{ __('messages.wallet_history') }}</a>
                </div>
            </div>

            <form method="post" action="{{ route('wallet.adjust', $customer->id) }}" class="row g-2 align-items-end" onsubmit="return confirm('{{ $t('confirm_adjust') }}')">
                @csrf
                <div class="col-md-2">
                    <label class="form-label">{{ $t('direction') }}</label>
                    <select name="direction" class="form-select"><option value="credit">{{ $t('credit') }}</option><option value="debit">{{ $t('debit') }}</option></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ $t('bucket') }}</label>
                    <select name="bucket" class="form-select"><option value="cash">{{ $t('cash') }}</option><option value="bonus">{{ $t('bonus') }}</option></select>
                </div>
                <div class="col-md-2"><label class="form-label">{{ $t('amount') }}</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">{{ $t('expiry_days') }}</label><input type="number" min="1" name="expiry_days" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">{{ $t('reason') }}</label><input name="reason" minlength="3" maxlength="500" class="form-control" required></div>
                <div class="col-md-1"><button class="btn btn-primary w-100">{{ $t('save') }}</button></div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-sm">
                    <thead><tr><th>{{ $t('date') }}</th><th>{{ $t('type') }}</th><th>{{ $t('amount') }}</th><th>{{ $t('cash') }}</th><th>{{ $t('bonus') }}</th><th>{{ $t('balance_after') }}</th><th>{{ $t('note') }}</th></tr></thead>
                    <tbody>
                    @forelse ($customerHistory as $row)
                        @php $data = json_decode($row->activity_data ?? '{}', true) ?: []; @endphp
                        <tr>
                            <td>{{ $row->created_at }}</td>
                            <td>{{ $row->source ? __('wallet_ledger.source.'.$row->source) : $row->activity_type }}</td>
                            <td class="{{ $row->direction === 'debit' ? 'text-danger' : 'text-success' }}">{{ $row->direction === 'debit' ? '−' : '+' }}{{ $money($row->amount ?? ($data['credit_debit_amount'] ?? 0)) }}</td>
                            <td>{{ $row->direction ? $money($row->cash_amount) : '—' }}</td>
                            <td>{{ $row->direction ? $money($row->bonus_amount) : '—' }}</td>
                            <td>{{ $row->balance_after !== null ? $money($row->balance_after) : '—' }}</td>
                            <td>{{ $row->activity_message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ $t('no_records') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endcan
@endsection

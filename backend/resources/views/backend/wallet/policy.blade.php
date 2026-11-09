@extends('backend.layouts.app')

@php $t = fn ($k) => __('wallet_ledger.admin.'.$k); @endphp

@section('title') {{ $t('policy') }} @endsection

@section('content')
@include('backend.wallet._nav')

@php
    $sections = [
        $t('policy_refunds') => [
            ['wallet_refund_on_cancel', 'bool'],
            ['wallet_free_cancel_hours', 'number'],
            ['wallet_late_cancel_fee_percent', 'number'],
        ],
        $t('policy_withdrawals') => [
            ['wallet_withdrawal_enabled', 'bool'],
            ['wallet_withdrawal_min', 'number'],
            ['wallet_withdrawal_requires_approval', 'bool'],
        ],
        $t('policy_spending') => [
            ['wallet_spend_bonus_first', 'bool'],
            ['wallet_points_conversion_enabled', 'bool'],
            ['wallet_points_conversion_min', 'number'],
        ],
        $t('policy_topup') => [
            ['wallet_topup_min', 'number'],
            ['wallet_topup_max', 'number'],
            ['wallet_topup_presets', 'text'],
        ],
    ];
@endphp

<form method="post" action="{{ route('wallet.policy.save') }}">
    @csrf
    @foreach ($sections as $heading => $items)
        <div class="card mb-3">
            <div class="card-body">
                <h5>{{ $heading }}</h5>
                <div class="row g-3">
                    @foreach ($items as [$key, $type])
                        <div class="col-md-4">
                            <label class="form-label">{{ __('wallet_ledger.policy.'.$key) }}</label>
                            @if ($type === 'bool')
                                <select name="{{ $key }}" class="form-select">
                                    <option value="1" @selected((int) old($key, $values[$key]) === 1)>{{ $t('yes') }}</option>
                                    <option value="0" @selected((int) old($key, $values[$key]) === 0)>{{ $t('no') }}</option>
                                </select>
                            @else
                                <input type="{{ $type }}" name="{{ $key }}" value="{{ old($key, $values[$key]) }}" class="form-control" @if($type === 'number') step="0.01" min="0" @endif required>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
    <button class="btn btn-primary">{{ $t('save') }}</button>
</form>
@endsection

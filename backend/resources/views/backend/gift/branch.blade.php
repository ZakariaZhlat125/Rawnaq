@extends('backend.layouts.app')

@php $t = fn ($k) => __('gift_system.admin.'.$k); @endphp

@section('title') {{ $t('redeem') }} @endsection

@section('content')
@include('backend.gift._nav')

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">{{ $t('enter_code') }}</label>
                <input name="code" value="{{ request('code') }}" class="form-control form-control-lg text-uppercase" dir="ltr" placeholder="SAMI-XXXX-XXXX" autocomplete="off" autofocus required>
            </div>
            <div class="col-auto"><button class="btn btn-primary btn-lg">{{ $t('check') }}</button></div>
        </form>
        @if ($error)<div class="alert alert-danger mt-3 mb-0">{{ $error }}</div>@endif
    </div>
</div>

@if ($gift)
    @php $usable = $gift->isUsable(); @endphp
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1"><a href="{{ route('gifts.show', $gift->id) }}">#{{ $gift->id }}</a> — {{ __('gift_system.type.'.$gift->type) }}
                        @if ($gift->isValue()) · {{ number_format($gift->value_amount, 2) }} SAR @endif</h5>
                    @if (! $gift->isValue())
                        <div>{{ $gift->services_list->pluck('name')->merge($gift->packages_list->pluck('name'))->implode('، ') }}</div>
                    @endif
                    <small class="text-muted">{{ $t('recipient') }}: {{ $gift->recipient_name ?: '—' }} · {{ $t('expires_at') }}: {{ $gift->expires_at?->toDateString() ?? '—' }}</small>
                </div>
                <span class="badge fs-6 align-self-start bg-{{ $usable ? 'success' : 'danger' }}">
                    {{ $usable ? $t('usable') : __('gift_system.status.'.(in_array($gift->gift_status, \App\Models\GiftCard::USABLE_STATUSES, true) ? 'expired' : $gift->gift_status)) }}
                </span>
            </div>
        </div>
    </div>

    @if ($usable)
        <div class="card">
            <div class="card-body">
                @unless ($gift->recipient_user_id)
                    <form method="get" class="row g-2 align-items-end mb-3">
                        <input type="hidden" name="code" value="{{ request('code') }}">
                        <div class="col-md-4"><label class="form-label">{{ $t('customer') }}</label><input name="customer" value="{{ request('customer') }}" class="form-control" required></div>
                        <div class="col-auto"><button class="btn btn-outline-primary">{{ $t('find_customer') }}</button></div>
                    </form>
                    @if (request()->filled('customer') && ! $customer)<div class="alert alert-warning">{{ $t('customer_not_found') }}</div>@endif
                @endunless

                @if ($customer)
                    <p class="mb-2"><b>#{{ $customer->id }} {{ trim($customer->first_name.' '.$customer->last_name) }}</b> <span dir="ltr">{{ $customer->mobile }}</span></p>
                    <form method="post" action="{{ route('gifts.redeem.apply') }}" class="row g-2 align-items-end">
                        @csrf
                        <input type="hidden" name="code" value="{{ request('code') }}">
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                        @if ($gift->isValue())
                            <div class="col-auto"><button class="btn btn-success">{{ $t('apply_value') }}</button></div>
                        @else
                            @if ($bookings->isEmpty() && $gift->recipient_user_id && ! request()->filled('customer'))
                                <div class="col-12 text-muted small">
                                    <a href="{{ route('gifts.redeem', ['code' => request('code'), 'customer' => $customer->id]) }}">{{ $t('choose_booking') }}</a>
                                </div>
                            @elseif ($bookings->isEmpty())
                                <div class="col-12"><div class="alert alert-warning mb-0">{{ $t('no_open_bookings') }}</div></div>
                            @else
                                <div class="col-md-6">
                                    <label class="form-label">{{ $t('choose_booking') }}</label>
                                    <select name="booking_id" class="form-select" required>
                                        @foreach ($bookings as $b)
                                            <option value="{{ $b->id }}">#{{ $b->id }} — {{ \Carbon\Carbon::parse($b->start_date_time)->format('Y-m-d H:i') }} — {{ $b->services->pluck('service_name')->filter()->implode('، ') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto"><button class="btn btn-success">{{ $t('apply_service') }}</button></div>
                            @endif
                        @endif
                    </form>
                @endif
            </div>
        </div>
    @endif
@endif
@endsection

@extends('backend.layouts.app')

@php
    $t = fn ($k) => __('gift_system.admin.'.$k);
    $statusClass = ['paid' => 'info', 'sent' => 'primary', 'claimed' => 'warning', 'redeemed' => 'success', 'expired' => 'secondary', 'cancelled' => 'danger', 'refunded' => 'danger', 'pending_payment' => 'light'];
@endphp

@section('title') {{ $t('title') }} @endsection

@section('content')
@include('backend.gift._nav')

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end mb-3">
            <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ $t('search') }}"></div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ $t('status') }}: {{ $t('all') }}</option>
                    @foreach (['paid', 'sent', 'claimed', 'redeemed', 'expired', 'cancelled', 'refunded'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('gift_system.status.'.$s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select form-select-sm">
                    <option value="">{{ $t('type') }}: {{ $t('all') }}</option>
                    @foreach (['service', 'package', 'value'] as $s)
                        <option value="{{ $s }}" @selected(request('type') === $s)>{{ __('gift_system.type.'.$s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="source" class="form-select form-select-sm">
                    <option value="">{{ $t('source') }}: {{ $t('all') }}</option>
                    @foreach (['customer', 'admin', 'corporate', 'campaign'] as $s)
                        <option value="{{ $s }}" @selected(request('source') === $s)>{{ __('gift_system.source.'.$s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" aria-label="{{ $t('from') }}"></div>
            <div class="col-auto"><input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" aria-label="{{ $t('to') }}"></div>
            <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">{{ $t('filter') }}</button></div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr>
                    <th>{{ $t('id') }}</th><th>{{ $t('type') }}</th><th>{{ $t('value') }}</th><th>{{ $t('buyer') }}</th>
                    <th>{{ $t('recipient') }}</th><th>{{ $t('code') }}</th><th>{{ $t('status') }}</th><th>{{ $t('expires_at') }}</th><th>{{ $t('created_at') }}</th>
                </tr></thead>
                <tbody>
                @forelse ($gifts as $gift)
                    @php $status = in_array($gift->gift_status, \App\Models\GiftCard::USABLE_STATUSES, true) && $gift->isExpired() ? 'expired' : $gift->gift_status; @endphp
                    <tr>
                        <td><a href="{{ route('gifts.show', $gift->id) }}">#{{ $gift->id }}</a></td>
                        <td>{{ __('gift_system.type.'.$gift->type) }}<br><small class="text-muted">{{ __('gift_system.source.'.$gift->source) }}</small></td>
                        <td>{{ number_format($gift->faceValue(), 2) }}</td>
                        <td>{{ $gift->user ? trim($gift->user->first_name.' '.$gift->user->last_name) : ($gift->sender_name ?: '—') }}</td>
                        <td>{{ $gift->recipient_name ?: '—' }}<br><small class="text-muted" dir="ltr">{{ $gift->recipient_phone }}</small></td>
                        <td dir="ltr"><small>{{ $gift->maskedCode() ?? '—' }}</small></td>
                        <td><span class="badge bg-{{ $statusClass[$status] ?? 'light' }}">{{ __('gift_system.status.'.$status) }}</span></td>
                        <td>{{ $gift->expires_at?->toDateString() ?? '—' }}</td>
                        <td>{{ $gift->created_at?->toDateString() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">{{ $t('no_records') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $gifts->links() }}
    </div>
</div>
@endsection

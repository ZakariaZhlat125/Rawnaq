@extends('backend.layouts.app')

@php $t = fn ($k) => __('wallet_ledger.admin.'.$k); @endphp

@section('title') {{ $t('offers') }} @endsection

@section('content')
@include('backend.wallet._nav')

@php
    $fields = function ($offer = null) use ($t) {
        return [
            ['title_ar', $t('title_ar'), 'text', $offer?->title_ar, true],
            ['title_en', $t('title_en'), 'text', $offer?->title_en, false],
            ['min_amount', $t('min_amount'), 'number', $offer?->min_amount, true],
            ['bonus_amount', $t('bonus_amount'), 'number', $offer?->bonus_amount, true],
            ['bonus_expiry_days', $t('expiry_days'), 'number', $offer?->bonus_expiry_days, false],
            ['max_uses_per_user', $t('max_uses'), 'number', $offer?->max_uses_per_user, false],
            ['starts_at', $t('starts_at'), 'date', $offer?->starts_at?->toDateString(), false],
            ['ends_at', $t('ends_at'), 'date', $offer?->ends_at?->toDateString(), false],
        ];
    };
@endphp

<div class="card mb-3">
    <div class="card-body">
        <h5>{{ $t('add_offer') }}</h5>
        <form method="post" action="{{ route('wallet.offers.store') }}" class="row g-2 align-items-end">
            @csrf
            @foreach ($fields() as [$name, $label, $type, $value, $required])
                <div class="col-md-3">
                    <label class="form-label">{{ $label }}</label>
                    <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}" class="form-control" @if($type === 'number') step="0.01" min="0" @endif @required($required)>
                </div>
            @endforeach
            <div class="col-md-3 form-check ms-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="new-active" checked>
                <label class="form-check-label" for="new-active">{{ $t('active') }}</label>
            </div>
            <div class="col-md-2"><button class="btn btn-primary">{{ $t('save') }}</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @forelse ($offers as $offer)
            <form method="post" action="{{ route('wallet.offers.update', $offer) }}" class="row g-2 align-items-end border-bottom pb-3 mb-3">
                @csrf @method('PUT')
                @foreach ($fields($offer) as [$name, $label, $type, $value, $required])
                    <div class="col-md-3">
                        <label class="form-label small">{{ $label }}</label>
                        <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" class="form-control form-control-sm" @if($type === 'number') step="0.01" min="0" @endif @required($required)>
                    </div>
                @endforeach
                <div class="col-md-3 form-check ms-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="active-{{ $offer->id }}" @checked($offer->is_active)>
                    <label class="form-check-label" for="active-{{ $offer->id }}">{{ $t('active') }}</label>
                </div>
                <div class="col-md-4 small text-muted">
                    {{ $t('uses') }}: {{ $uses[$offer->id]->uses ?? 0 }} · {{ $t('bonus_granted') }}: {{ number_format($uses[$offer->id]->total ?? 0, 2) }}
                </div>
                <div class="col-md-2"><button class="btn btn-sm btn-primary">{{ $t('save') }}</button></div>
            </form>
        @empty
            <p class="text-muted text-center mb-0">{{ $t('no_records') }}</p>
        @endforelse
    </div>
</div>
@endsection

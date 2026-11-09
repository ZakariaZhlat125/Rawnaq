@extends('backend.layouts.app')

@php
    $t = fn ($k) => __('gift_system.admin.'.$k);
    $money = fn ($v) => number_format((float) $v, 2);
    $sum = fn ($rows) => [collect($rows)->sum('c'), collect($rows)->sum('total')];
@endphp

@section('title') {{ $t('report') }} @endsection

@section('content')
@include('backend.gift._nav')

<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-auto"><label class="form-label mb-0">{{ $t('from') }}</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><label class="form-label mb-0">{{ $t('to') }}</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">{{ $t('filter') }}</button></div>
</form>

<div class="row g-3 mb-3">
    @foreach ([
        ['sales', $sum($sales)],
        ['issued', $sum($issued)],
        ['redeemed_period', [$redeemed->c ?? 0, $redeemed->total ?? 0]],
        ['outstanding', $sum($outstanding)],
        ['expired_period', [$expired->c ?? 0, $expired->total ?? 0]],
        ['cancelled_period', [$cancelled->c ?? 0, $cancelled->total ?? 0]],
    ] as [$label, [$count, $total]])
        <div class="col-md-4 col-xl-2">
            <div class="card h-100"><div class="card-body">
                <small class="text-muted">{{ $t($label) }}</small>
                <h4 class="mb-0">{{ $money($total) }} <small>SAR</small></h4>
                <small>{{ $t('count') }}: {{ $count }}</small>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h6>{{ $t('outstanding') }} — {{ $t('type') }}</h6>
            <table class="table table-sm mb-0">
                @foreach (['service', 'package', 'value'] as $type)
                    <tr><td>{{ __('gift_system.type.'.$type) }}</td><td>{{ $outstanding[$type]->c ?? 0 }}</td><td>{{ $money($outstanding[$type]->total ?? 0) }}</td></tr>
                @endforeach
            </table>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h6>{{ $t('funnel') }}</h6>
            <table class="table table-sm mb-0">
                @foreach (['total' => 'paid', 'sent' => 'sent', 'opened' => 'opened', 'claimed' => 'claimed', 'redeemed' => 'redeemed'] as $col => $event)
                    <tr><td>{{ __('gift_system.event.'.$event) }}</td><td>{{ $funnel->{$col} ?? 0 }}</td>
                        <td class="text-muted">{{ ($funnel->total ?? 0) > 0 ? round(($funnel->{$col} ?? 0) * 100 / $funnel->total) : 0 }}%</td></tr>
                @endforeach
            </table>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h6>{{ $t('by_occasion') }}</h6>
            <table class="table table-sm mb-0">
                @forelse ($byOccasion as $row)
                    <tr><td>{{ __('gift_system.occasion.'.$row->occasion) }}</td><td>{{ $row->c }}</td></tr>
                @empty
                    <tr><td class="text-muted">{{ $t('no_records') }}</td></tr>
                @endforelse
            </table>
        </div></div>
    </div>
</div>
@endsection

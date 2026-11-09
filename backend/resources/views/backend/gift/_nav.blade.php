@php
    $tabs = [
        ['app.gift', 'view_gift', 'list'],
        ['gifts.redeem', 'redeem_gift_branch', 'redeem'],
        ['gifts.batches', 'manage_gift_batches', 'batches'],
        ['gift-designs.index', 'manage_gift_designs', 'designs'],
        ['gifts.report', 'view_gift_reports', 'report'],
        ['gifts.settings', 'manage_gifts', 'settings'],
    ];
@endphp
<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach ($tabs as [$route, $permission, $label])
        @can($permission)
            <a href="{{ route($route) }}" class="btn btn-sm {{ request()->routeIs($route) ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('gift_system.admin.'.$label) }}</a>
        @endcan
    @endforeach
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

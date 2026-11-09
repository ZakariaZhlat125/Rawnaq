@php $t = fn ($k) => __('wallet_ledger.admin.'.$k); @endphp
<div class="d-flex flex-wrap gap-2 mb-3">
    @can('view_wallet_admin')
        <a href="{{ route('wallet.index') }}" class="btn btn-sm {{ request()->routeIs('wallet.index') ? 'btn-primary' : 'btn-outline-primary' }}">{{ $t('title') }}</a>
    @endcan
    @can('manage_wallet_withdrawals')
        <a href="{{ route('wallet.withdrawals') }}" class="btn btn-sm {{ request()->routeIs('wallet.withdrawals') ? 'btn-primary' : 'btn-outline-primary' }}">{{ $t('withdrawals') }}</a>
    @endcan
    @can('manage_wallet_offers')
        <a href="{{ route('wallet.offers') }}" class="btn btn-sm {{ request()->routeIs('wallet.offers') ? 'btn-primary' : 'btn-outline-primary' }}">{{ $t('offers') }}</a>
    @endcan
    @can('manage_wallet_policy')
        <a href="{{ route('wallet.policy') }}" class="btn btn-sm {{ request()->routeIs('wallet.policy') ? 'btn-primary' : 'btn-outline-primary' }}">{{ $t('policy') }}</a>
    @endcan
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger mb-3">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

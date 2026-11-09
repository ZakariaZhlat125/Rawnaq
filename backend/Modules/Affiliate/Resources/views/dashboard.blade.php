@extends('affiliate::layouts.master')

@section('title', __('affiliate.affiliate_dashboard_title'))

@section('content')
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="card card-block card-stretch card-height">
            <div class="card-body">
                <h6 class="text-muted">{{ __('affiliate.visitors') }}</h6>
                <h3 class="mb-0">{{ $totalVisitors }}</h3>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card card-block card-stretch card-height">
            <div class="card-body">
                <h6 class="text-muted">{{ __('affiliate.conversions') }}</h6>
                <h3 class="mb-0">{{ $totalConversions }}</h3>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card card-block card-stretch card-height">
            <div class="card-body">
                <h6 class="text-muted">{{ __('affiliate.earnings_total') }}</h6>
                <h3 class="mb-0">{{ \Currency::format($totalEarnings) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card card-block card-stretch card-height">
            <div class="card-body">
                <h6 class="text-muted">{{ __('affiliate.available_balance') }}</h6>
                <h3 class="mb-0 text-success">{{ \Currency::format($availableEarnings) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">{{ __('affiliate.share_referral_link') }}</h5>
    </div>
    <div class="card-body">
        <label class="form-label">{{ __('affiliate.your_referral_link') }}</label>
        <div class="input-group">
            <input id="affiliateReferralLink" type="text" class="form-control" value="{{ $referralLink }}" readonly>
            <button id="copyAffiliateReferralLink" class="btn btn-primary" type="button">{{ __('affiliate.copy_link') }}</button>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">{{ __('affiliate.last_30_days_visitors') }}</h5>
    </div>
    <div class="card-body">
        <canvas id="visitorsChart" height="100"></canvas>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card card-block">
            <div class="card-header">
                <h5>{{ __('affiliate.latest_conversions') }}</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse ($lastConversions as $conv)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('affiliate.order_number') }} #{{ $conv->order_id }}</span>
                            <span class="text-success">{{ \Currency::format($conv->commission) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('affiliate.no_conversions') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('visitorsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: @json(__('affiliate.visitors')),
                data: @json($chartData),
                borderWidth: 2,
                borderColor: '#4e73df',
                fill: false,
                tension: 0.3
            }]
        }
    });

    const copyButton = document.getElementById('copyAffiliateReferralLink');
    const linkInput = document.getElementById('affiliateReferralLink');

    if (copyButton && linkInput) {
        copyButton.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(linkInput.value);
                copyButton.innerText = @json(__('affiliate.copied'));
                setTimeout(() => {
                    copyButton.innerText = @json(__('affiliate.copy_link'));
                }, 1200);
            } catch (error) {
                linkInput.select();
                document.execCommand('copy');
            }
        });
    }
</script>
@endpush

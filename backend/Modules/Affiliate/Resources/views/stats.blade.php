@extends('affiliate::layouts.master')

@section('title', 'الإحصائيات التفصيلية')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5>إحصائيات الزوار والتحويلات آخر 30 يوم</h5>
            </div>
            <div class="card-body">
                <canvas id="statsChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let ctx = document.getElementById('statsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($visitorLabels),
        datasets: [
            {
                label: 'الزوار',
                data: @json($visitorData),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78,115,223,0.1)',
                fill: true,
                tension: 0.3
            },
            {
                label: 'التحويلات',
                data: @json($conversionData),
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28,200,138,0.1)',
                fill: true,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            x: {
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 15
                }
            }
        }
    }
});
</script>
@endpush

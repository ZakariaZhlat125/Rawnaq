@extends('backend.layouts.app')

@section('title')
{{ __('messagess.cancellation_of_reservation') }}
@endsection

@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<style>
    .card {
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .table th, .table td {
        vertical-align: middle;
    }
</style>
@endpush

@section('content')

<div class="container-fluid py-4" style="margin-top: 30px;">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-plus"></i> {{ __('messagess.add_new_reason') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{route('app.store')}}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('messagess.reason_name') }} (AR)</label>
                            <input type="text" name="reasonAR" class="form-control" placeholder="{{ __('messagess.enter_reason') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messagess.reason_name') }} (EN)</label>
                            <input type="text" name="reasonEN" class="form-control" placeholder="{{ __('messagess.enter_reason') }}">
                        </div>
                        @hasPermission('store_reject_reasons')
                        <button type="submit" class="btn btn-primary w-100">{{ __('messagess.add_reason') }}</button>
                        @endhasPermission
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fa-solid fa-list"></i> {{ __('messagess.reasons_list') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('messagess.reason_name') }} (AR)</th>
                                <th>{{ __('messagess.reason_name') }} (EN)</th>
                                <th>{{ __('messagess.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reasons as $index => $reason)
                            <tr>
                                <td>{{ $index + 1}}</td>
                                <td> {{$reason->name['ar']}} </td>
                                <td> {{$reason->name['en']}} </td>
                                <td>
                                    @hasPermission('update_reject_reasons')
                                    <button class="btn btn-sm btn-warning edit-btn"
                                        data-id="{{ $reason->id }}"
                                        data-ar="{{ $reason->name['ar'] }}"
                                        data-en="{{ $reason->name['en'] }}"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @endhasPermission
                                    @hasPermission('delete_reject_reasons')
                                    <a href="{{route('reject.destroy' , $reason->id)}}" class="btn btn-sm btn-danger"
                                       onclick="return confirm('{{ __('messagess.confirm_delete') }}');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                    @endhasPermission
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-chart-pie"></i> {{ __('messagess.cancellation_statistics') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="reasonsChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Edit Reason Modal -->
<div class="modal fade" id="editReasonModal" tabindex="-1" aria-labelledby="editReasonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editReasonForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title" id="editReasonModalLabel">{{ __('messagess.edit_reason') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messagess.close') }}"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">{{ __('messagess.reason_name') }} (AR)</label>
            <input type="text" name="reasonAR" id="editReasonAR" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ __('messagess.reason_name') }} (EN)</label>
            <input type="text" name="reasonEN" id="editReasonEN" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messagess.cancel') }}</button>
          <button type="submit" class="btn btn-primary">{{ __('messagess.save_changes') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('after-scripts')
<!-- مكتبة Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
@if(session('success'))
    toastr.success("{{ session('success') }}");
@endif

@if(session('error'))
    toastr.error("{{ session('error') }}");
@endif

@if(session('warning'))
    toastr.warning("{{ session('warning') }}");
@endif

@if(session('info'))
    toastr.info("{{ session('info') }}");
@endif
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const reasons = @json($reasons);
    const labels = reasons.map(r => r.name.ar);
    const dataValues = reasons.map(r => r.count);

    const ctx = document.getElementById('reasonsChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '{{ __("messagess.number_of_selections") }}',
                data: dataValues,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: '{{ __("messagess.most_common_cancellation_reasons") }}',
                    font: { size: 18 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
    
    const editButtons = document.querySelectorAll('.edit-btn');
    const editForm = document.getElementById('editReasonForm');
    const reasonARInput = document.getElementById('editReasonAR');
    const reasonENInput = document.getElementById('editReasonEN');
        editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const id = button.dataset.id;
            const ar = button.dataset.ar;
            const en = button.dataset.en;

            reasonARInput.value = ar;
            reasonENInput.value = en;

            editForm.action = `/reject/update/${id}`;

            const modal = new bootstrap.Modal(document.getElementById('editReasonModal'));
            modal.show();
        });
    });
});
</script>
@endpush

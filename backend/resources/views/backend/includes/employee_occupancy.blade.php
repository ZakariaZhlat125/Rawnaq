<div class="col-12">
  <section class="card" aria-labelledby="employee-occupancy-title">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <h4 id="employee-occupancy-title" class="card-title mb-0">{{ __('dashboard.occupancy_title') }}</h4>
      <span class="badge bg-soft-primary text-primary">{{ $date_range }}</span>
    </div>
    <div class="card-body">
      <p class="text-muted small mb-3">{{ __('dashboard.occupancy_description') }}</p>
      <div class="table-responsive" style="max-height:480px;overflow:auto">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">{{ __('dashboard.occupancy_employee') }}</th>
              <th scope="col">{{ __('dashboard.occupancy_branch') }}</th>
              <th scope="col">{{ __('dashboard.occupancy_booked') }}</th>
              <th scope="col">{{ __('dashboard.occupancy_available') }}</th>
              <th scope="col" style="min-width:180px">{{ __('dashboard.occupancy_rate') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($data['employee_occupancy'] as $employee)
              <tr>
                <td>{{ $employee['name'] }}</td>
                <td>{{ $employee['branches'] ?: '—' }}</td>
                <td>{{ number_format($employee['booked_minutes'] / 60, 2) }}</td>
                <td>{{ number_format($employee['available_minutes'] / 60, 2) }}</td>
                <td>
                  @if($employee['percentage'] !== null)
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height:8px" role="progressbar"
                           aria-label="{{ __('dashboard.occupancy_rate') }}: {{ $employee['name'] }}"
                           aria-valuenow="{{ $employee['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-primary" style="width:{{ $employee['percentage'] }}%"></div>
                      </div>
                      <strong dir="ltr">{{ number_format($employee['percentage'], 1) }}%</strong>
                    </div>
                  @else
                    <span class="text-muted" title="{{ __('dashboard.occupancy_no_hours') }}">{{ __('dashboard.occupancy_unavailable') }}</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-4">{{ __('dashboard.occupancy_empty') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <p class="text-muted small mt-3 mb-0">{{ __('dashboard.occupancy_schedule_note') }}</p>
    </div>
  </section>
</div>

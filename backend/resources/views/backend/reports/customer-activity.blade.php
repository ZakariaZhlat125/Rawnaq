@extends('backend.layouts.app')
@section('title') {{ $module_title }} @endsection
@section('content')
<div class="card"><div class="card-body">
    <p class="text-muted">{{ __('customer_activity.note') }}</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="get" class="row g-3">
        <div class="col-md-3"><label for="from">{{ __('customer_activity.from') }}</label><input id="from" type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="col-md-3"><label for="to">{{ __('customer_activity.to') }}</label><input id="to" type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
        <div class="col-md-3"><label for="sort">{{ __('customer_activity.sort') }}</label><select id="sort" name="sort" class="form-control">
            <option value="spending" @selected(request('sort', 'spending') === 'spending')>{{ __('customer_activity.spending') }}</option>
            <option value="activity" @selected(request('sort') === 'activity')>{{ __('customer_activity.activity') }}</option>
        </select></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary">{{ __('customer_activity.filter') }}</button></div>
    </form>
</div></div>
<div class="card"><div class="card-body table-responsive">
    <table class="table table-striped"><thead><tr>
        @foreach(['customer', 'phone', 'bookings', 'gifts', 'orders', 'total_activity', 'total_spending'] as $column)
            <th>{{ __('customer_activity.'.$column) }}</th>
        @endforeach
    </tr></thead><tbody>
    @forelse($customers as $customer)
        <tr><td>{{ $customer['user']?->full_name ?? '#'.$customer['user_id'] }}</td><td>{{ $customer['user']?->mobile ?? '—' }}</td>
        <td>{{ $customer['bookings'] }}</td><td>{{ $customer['gifts'] }}</td><td>{{ $customer['orders'] }}</td>
        <td>{{ $customer['activity'] }}</td><td>{{ Currency::format($customer['spending']) }}</td></tr>
    @empty
        <tr><td colspan="7">{{ __('customer_activity.empty') }}</td></tr>
    @endforelse
    </tbody></table>
    {{ $customers->links() }}
</div></div>
@endsection

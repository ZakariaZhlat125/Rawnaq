@extends('backend.layouts.app')

@section('title', __('cafe.orders'))

@section('content')
    @include('backend.cafe.partials.alerts')
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">{{ __('cafe.orders') }}</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('cafe.order_number') }}</th>
                            <th>{{ __('cafe.table') }}</th>
                            <th>{{ __('cafe.items') }}</th>
                            <th>{{ __('cafe.total') }}</th>
                            <th>{{ __('cafe.status') }}</th>
                            <th>{{ __('cafe.time') }}</th>
                            <th class="text-end">{{ __('cafe.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><strong>{{ $order->order_number }}</strong></td>
                                <td>{{ $order->table?->name }}</td>
                                <td>{{ $order->items->sum('quantity') }}</td>
                                <td>{{ number_format($order->total, 2) }}</td>
                                <td>{!! $order->status_label !!}</td>
                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-soft-primary" href="{{ route('backend.cafe.orders.show', $order) }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">{{ __('cafe.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        </div>
    </div>
@endsection

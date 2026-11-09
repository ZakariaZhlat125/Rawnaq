@extends('backend.layouts.app')

@section('title', __('cafe.order_details'))

@section('content')
    @include('backend.cafe.partials.alerts')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $order->order_number }}</h4>
                    {!! $order->status_label !!}
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('cafe.item') }}</th>
                                    <th>{{ __('cafe.quantity') }}</th>
                                    <th>{{ __('cafe.price') }}</th>
                                    <th>{{ __('cafe.subtotal') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->item_name }}</strong>
                                            @if ($item->notes)
                                                <div class="small text-muted">{{ $item->notes }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->item_price, 2) }}</td>
                                        <td>{{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">{{ __('cafe.total') }}</th>
                                    <th>{{ number_format($order->total, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if ($order->notes)
                        <div class="alert alert-light mb-0">{{ $order->notes }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('cafe.order_info') }}</h5></div>
                <div class="card-body">
                    <p><strong>{{ __('cafe.table') }}:</strong> {{ $order->table?->name }}</p>
                    <p><strong>{{ __('cafe.time') }}:</strong> {{ $order->created_at?->format('Y-m-d H:i') }}</p>
                    <p><strong>{{ __('cafe.customer_name') }}:</strong> {{ $order->customer_name ?: '-' }}</p>
                    <p><strong>{{ __('cafe.customer_phone') }}:</strong> {{ $order->customer_phone ?: '-' }}</p>

                    <form method="POST" action="{{ route('backend.cafe.orders.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <label class="form-label">{{ __('cafe.change_status') }}</label>
                        <select name="status" class="form-select mb-3">
                            @foreach (['pending', 'preparing', 'ready', 'delivered', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected($order->status === $status)>{{ __('cafe.status_'.$status) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary w-100" type="submit">{{ __('cafe.update_status') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

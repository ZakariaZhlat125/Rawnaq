@php
use App\Models\GiftCard;
@endphp

@extends('backend.layouts.app')

@section('title')
{{ __($module_action) }} {{ __($module_title) }}
@endsection

@push('after-styles')
<style>

    /* ===== Invoice Card ===== */
    .invoice-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 18px 25px;
        margin-bottom: 15px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.3s;
        border: 1px solid #eee;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .invoice-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }

    .invoice-details {
        display: none;
        margin-top: 15px;
    }

    /* ===== Invoice Box ===== */
    .invoice-box {
        background: #fff;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #eee;
    }

    .invoice-header {
        display: flex;
        justify-content: space-between;
        border-bottom: 2px solid #f1f1f1;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .invoice-title {
        font-size: 22px;
        font-weight: bold;
        color: #333;
    }

    .invoice-meta {
        font-size: 14px;
        color: #777;
    }

    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .invoice-table th {
        background: #f8f9fa;
        padding: 10px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #eee;
    }

    .invoice-table td {
        padding: 10px;
        border-bottom: 1px solid #f1f1f1;
    }

    .invoice-summary {
        margin-top: 25px;
        background: #f9fafc;
        padding: 20px;
        border-radius: 10px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .summary-total {
        font-size: 18px;
        font-weight: bold;
        color: #000;
        border-top: 2px solid #ddd;
        padding-top: 10px;
    }

    .payment-method {
        margin-top: 15px;
        font-weight: 600;
        color: #0d6efd;
    }

</style>
@endpush

@section('content')
<div class="card">
<div class="card-body">

<h3 class="mb-4">{{ __('messages.invoice_cards_list') }}</h3>

@foreach($invoices as $invoice)

<div class="invoice-card" onclick="toggleInvoiceDetails({{ $invoice->id }})">
    <div>
        <strong>{{ $invoice->user->first_name }} {{ $invoice->user->last_name }}</strong><br>
        <small>#INV-{{ $invoice->id }}</small>
    </div>
    <div>
        {{ $invoice->created_at->format('Y-m-d H:i') }}
    </div>
</div>

<div id="invoice-details-{{ $invoice->id }}" class="invoice-details">

<div class="invoice-box">

    {{-- Header --}}
    <div class="invoice-header">
        <div>
            <div class="invoice-title">{{ __('Invoice') }}</div>
            <div class="invoice-meta">
                {{ __('Invoice No:') }} INV-{{ $invoice->id }} <br>
                {{ __('Date:') }} {{ $invoice->created_at->format('Y-m-d') }}
            </div>
        </div>

        <div class="invoice-meta">
            <strong>{{ __('Customer:') }}</strong><br>
            {{ $invoice->user->first_name }} {{ $invoice->user->last_name }}
        </div>
    </div>

    @php
        $bookings = $invoice->bookings;
        $bookingsGift = $invoice->gifts;
        $products = $invoice->products;
    @endphp

    {{-- Table --}}
    <table class="invoice-table">
        <thead>
            <tr>
                <th>{{ __('Item') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('admin_ui.price') }}</th>
            </tr>
        </thead>
        <tbody>

        @foreach($bookings as $booking)
            @foreach($booking->services as $service)
                <tr>
                    <td>{{ $service->service_name }}</td>
                    <td>{{ __('admin_ui.service') }}</td>
                    <td>{{ $service->service_price - ($service->discount_amount ?? 0) }} {{ __('SR') }}</td>
                </tr>
            @endforeach
        @endforeach

        @foreach($bookingsGift as $gift)
            <tr>
                <td>{{ __('Gift Card') }} ({{ $gift->recipient_name }})</td>
                <td>{{ __('Gift') }}</td>
                <td>{{ $gift->subtotal }} {{ __('SR') }}</td>
            </tr>
        @endforeach

        @foreach($products as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ __('Product') }}</td>
                <td>{{ $product->price ?? $product->min_price ?? $product->max_price }} {{ __('SR') }}</td>
            </tr>
        @endforeach

        </tbody>
    </table>

    {{-- Summary --}}
    <div class="invoice-summary">

        <div class="summary-row">
            <div>{{ __('Discount') }}</div>
            <div>- {{ $invoice->discount_amount }} {{ __('SR') }}</div>
        </div>

        <div class="summary-row">
            <div>{{ __('Tax & Service') }}</div>
            <div>{{ $invoice->taxs_service }} {{ __('SR') }}</div>
        </div>

        <div class="summary-row summary-total">
            <div>{{ __('Total') }}</div>
            <div>{{ $invoice->final_total }} {{ __('SR') }}</div>
        </div>

        {{-- Payment Method --}}
        <div class="payment-method">
            {{ __('Payment Method:') }}
            {{ $invoice->payment_method ?? '' }}
        </div>

    </div>

</div>
</div>

@endforeach

</div>
</div>

<script>
function toggleInvoiceDetails(id) {
    const detailsDiv = document.getElementById(`invoice-details-${id}`);
    detailsDiv.style.display =
        detailsDiv.style.display === 'block' ? 'none' : 'block';
}
</script>

@endsection
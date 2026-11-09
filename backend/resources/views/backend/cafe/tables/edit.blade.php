@extends('backend.layouts.app')

@section('title', __('cafe.edit_table'))

@section('content')
    @include('backend.cafe.partials.alerts')
    <div class="card">
        <div class="card-header"><h4 class="mb-0">{{ __('cafe.edit_table') }}</h4></div>
        <div class="card-body">
            <form method="POST" action="{{ route('backend.cafe.tables.update', $table) }}">
                @method('PUT')
                @include('backend.cafe.tables.form')
            </form>
            <div class="mt-4 p-3 bg-light rounded">
                <img src="{{ $table->qr_url }}" alt="{{ $table->name }}" style="width: 160px; height: 160px;">
                <div class="mt-2"><a href="{{ $table->order_url }}" target="_blank">{{ $table->order_url }}</a></div>
            </div>
        </div>
    </div>
@endsection
